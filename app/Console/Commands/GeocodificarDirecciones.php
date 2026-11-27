<?php

namespace App\Console\Commands;

use App\Enums\EstadoPedido;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\Geocodificador;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

#[Signature('rutapyme:geocodificar {--todas : Vuelve a ubicar también las direcciones que ya tienen coordenadas}')]
#[Description('Ubica en el mapa las direcciones de clientes y pedidos abiertos usando OpenStreetMap')]
class GeocodificarDirecciones extends Command
{
    private const PAUSA_MICROSEGUNDOS = 1_100_000;

    public function handle(): int
    {
        $todas = (bool) $this->option('todas');

        $clientes = Cliente::query()
            ->withoutGlobalScopes()
            ->whereNotNull('direccion')
            ->when(! $todas, fn ($query) => $query->where(fn ($q) => $q->whereNull('latitud')->orWhereNull('longitud')))
            ->get();

        $pedidos = Pedido::query()
            ->withoutGlobalScopes()
            ->whereIn('estado', [EstadoPedido::Pendiente, EstadoPedido::Asignado, EstadoPedido::EnReparto])
            ->when(! $todas, fn ($query) => $query->where(fn ($q) => $q->whereNull('latitud')->orWhereNull('longitud')))
            ->get();

        if ($clientes->isEmpty() && $pedidos->isEmpty()) {
            $this->components->info('No hay direcciones por ubicar.');

            return self::SUCCESS;
        }

        $this->components->info("Ubicando {$clientes->count()} clientes y {$pedidos->count()} pedidos (1 consulta por segundo)…");

        [$okClientes, $sinClientes] = $this->ubicar($clientes, 'direccion');
        [$okPedidos, $sinPedidos] = $this->ubicar($pedidos, 'direccion_entrega');

        $this->newLine();
        $this->components->twoColumnDetail('Clientes ubicados', "{$okClientes} de {$clientes->count()}");
        $this->components->twoColumnDetail('Pedidos ubicados', "{$okPedidos} de {$pedidos->count()}");

        foreach ([...$sinClientes, ...$sinPedidos] as $direccion) {
            $this->components->warn("No encontrada: {$direccion}");
        }

        return self::SUCCESS;
    }

    /**
     * @param  iterable<int, Cliente|Pedido>  $registros
     * @return array{0: int, 1: list<string>}
     */
    private function ubicar(iterable $registros, string $campoDireccion): array
    {
        $ubicados = 0;
        $noEncontradas = [];

        foreach ($registros as $registro) {
            /** @var Model $registro */
            $resultado = Geocodificador::buscar($registro->{$campoDireccion}, $registro->comuna);

            if ($resultado === null) {
                $noEncontradas[] = trim($registro->{$campoDireccion}.', '.$registro->comuna, ', ');
            } else {
                $registro->forceFill(['latitud' => $resultado['lat'], 'longitud' => $resultado['lng']])->save();
                $ubicados++;
            }

            $this->output->write('.');
            usleep(self::PAUSA_MICROSEGUNDOS);
        }

        return [$ubicados, $noEncontradas];
    }
}

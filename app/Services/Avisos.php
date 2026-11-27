<?php

namespace App\Services;

use App\Enums\EstadoEntrega;
use App\Enums\RolUsuario;
use App\Filament\Pages\MisEntregas;
use App\Filament\Resources\Comprobantes\ComprobanteResource;
use App\Filament\Resources\Rutas\RutaResource;
use App\Models\Comprobante;
use App\Models\Entrega;
use App\Models\Repartidor;
use App\Models\Ruta;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

final class Avisos
{
    public static function entregaCerrada(Entrega $entrega, ?Comprobante $comprobante): void
    {
        $entrega->loadMissing(['pedido.cliente', 'repartidor']);
        $entregada = $entrega->estado === EstadoEntrega::Entregada;
        $pedido = $entrega->pedido;

        $aviso = Notification::make()
            ->title($entregada
                ? "Entrega confirmada · {$pedido->codigo}"
                : "No se pudo entregar · {$pedido->codigo}")
            ->body($entregada
                ? sprintf('%s recibió el pedido de %s. %s', $entrega->receptor_nombre ?: 'El cliente', $entrega->repartidor?->nombre, $comprobante ? "Comprobante {$comprobante->codigo}." : '')
                : sprintf('%s: %s', $entrega->repartidor?->nombre, $entrega->motivo_no_entrega ?: 'sin motivo informado'))
            ->icon($entregada ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle)
            ->iconColor($entregada ? 'success' : 'danger');

        if ($comprobante !== null) {
            $aviso->actions([
                Action::make('verComprobante')
                    ->label('Ver comprobante')
                    ->url(ComprobanteResource::getUrl('view', ['record' => $comprobante], panel: 'admin'))
                    ->markAsRead(),
            ]);
        }

        self::enviar($aviso, self::administradores($entrega->empresa_id));
    }

    public static function rutaAsignada(Ruta $ruta): void
    {
        $repartidor = Repartidor::query()->with('usuario')->find($ruta->repartidor_id);

        if ($repartidor?->usuario === null) {
            return;
        }

        $paradas = $ruta->entregas()->count();

        $aviso = Notification::make()
            ->title('Tienes una nueva ruta')
            ->body(sprintf(
                '%d %s para el %s%s.',
                $paradas,
                $paradas === 1 ? 'parada' : 'paradas',
                $ruta->fecha->format('d/m/Y'),
                $ruta->distancia_km !== null ? ' · '.CapacidadVehiculo::formatearKg($ruta->distancia_km).' km' : '',
            ))
            ->icon(Heroicon::OutlinedMap)
            ->iconColor('info')
            ->actions([
                Action::make('verEntregas')
                    ->label('Ver mis entregas')
                    ->url(MisEntregas::getUrl(panel: 'admin'))
                    ->markAsRead(),
            ]);

        self::enviar($aviso, new Collection([$repartidor->usuario]));
    }

    public static function rutaFinalizada(Ruta $ruta): void
    {
        $aviso = Notification::make()
            ->title('Ruta completada')
            ->body('Todas las entregas de la ruta quedaron cerradas.')
            ->icon(Heroicon::OutlinedFlag)
            ->iconColor('success')
            ->actions([
                Action::make('verRuta')
                    ->label('Ver ruta')
                    ->url(RutaResource::getUrl('view', ['record' => $ruta], panel: 'admin'))
                    ->markAsRead(),
            ]);

        self::enviar($aviso, self::administradores($ruta->empresa_id));
    }

    /**
     * @return Collection<int, User>
     */
    private static function administradores(string $empresaId): Collection
    {
        return User::query()
            ->where('empresa_id', $empresaId)
            ->where('rol', RolUsuario::Administrador)
            ->where('activo', true)
            ->get();
    }

    /**
     * @param  Collection<int, User>  $usuarios
     */
    private static function enviar(Notification $aviso, Collection $usuarios): void
    {
        if ($usuarios->isEmpty()) {
            return;
        }

        try {
            foreach ($usuarios as $usuario) {
                $usuario->notifyNow($aviso->toDatabase());
            }
        } catch (Throwable $excepcion) {
            report($excepcion);
        }
    }
}

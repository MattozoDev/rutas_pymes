<?php

namespace App\Filament\Widgets;

use App\Enums\ResultadoEntrega;
use App\Filament\Resources\Comprobantes\ComprobanteResource;
use App\Models\Comprobante;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class UltimasEntregas extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?bool $isLazyLoaded = false;

    protected string $view = 'filament.widgets.ultimas-entregas';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $zona = config('app.zona_horaria_negocio');

        $filas = Comprobante::query()
            ->join('pedidos', 'pedidos.id', '=', 'comprobantes.pedido_id')
            ->join('clientes', 'clientes.id', '=', 'pedidos.cliente_id')
            ->join('repartidores', 'repartidores.id', '=', 'comprobantes.repartidor_id')
            ->latest('comprobantes.emitido_at')
            ->limit(6)
            ->get([
                'comprobantes.id', 'comprobantes.codigo', 'comprobantes.resultado', 'comprobantes.emitido_at',
                'comprobantes.receptor_nombre', 'comprobantes.motivo_no_entrega', 'comprobantes.enviado_cliente_at',
                'pedidos.codigo as pedido_codigo', 'pedidos.direccion_entrega', 'pedidos.comuna',
                'clientes.nombre as cliente_nombre', 'repartidores.nombre as repartidor_nombre',
            ]);

        return [
            'entregas' => $filas->map(fn (Comprobante $c): array => [
                'url' => ComprobanteResource::getUrl('view', ['record' => $c->id]),
                'entregado' => $c->resultado === ResultadoEntrega::Entregado,
                'resultado' => $c->resultado?->getLabel(),
                'codigo' => $c->codigo,
                'pedido' => $c->pedido_codigo,
                'cliente' => $c->cliente_nombre,
                'direccion' => trim($c->direccion_entrega.($c->comuna ? ', '.$c->comuna : '')),
                'detalle' => $c->resultado === ResultadoEntrega::Entregado
                    ? 'Recibió '.($c->receptor_nombre ?: 'sin nombre')
                    : ($c->motivo_no_entrega ?: 'Sin motivo informado'),
                'repartidor' => $c->repartidor_nombre,
                'hora' => $c->emitido_at?->timezone($zona)->format('H:i'),
                'hace' => $c->emitido_at ? Carbon::parse($c->emitido_at)->locale('es')->diffForHumans() : null,
                'enviado' => $c->enviado_cliente_at !== null,
            ]),
            'urlTodos' => ComprobanteResource::getUrl('index'),
        ];
    }
}

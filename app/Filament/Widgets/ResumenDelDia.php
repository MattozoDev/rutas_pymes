<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoPedido;
use App\Enums\EstadoRepartidor;
use App\Filament\Resources\Comprobantes\ComprobanteResource;
use App\Filament\Resources\Pedidos\PedidoResource;
use App\Filament\Resources\Repartidores\RepartidorResource;
use App\Models\Pedido;
use App\Models\Repartidor;
use App\Services\CapacidadVehiculo;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenDelDia extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?bool $isLazyLoaded = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $hoy = today(config('app.zona_horaria_negocio'))->toDateString();

        $porEstado = Pedido::query()
            ->where('fecha_entrega', $hoy)
            ->toBase()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $pendientes = Pedido::resumenPorEstado()->get(EstadoPedido::Pendiente->value);
        $cargaPendiente = $pendientes->peso_kg ?? 0;
        $enRuta = Repartidor::query()->where('estado', EstadoRepartidor::EnRuta)->count();

        return [
            Stat::make('Pedidos del día', (string) $porEstado->sum())
                ->description('Ver todos los pedidos')
                ->descriptionIcon(Heroicon::ChevronRight)
                ->url(PedidoResource::getUrl('index')),
            Stat::make('Pendientes de asignar', (string) ($pendientes->total ?? 0))
                ->description('Carga pendiente: '.CapacidadVehiculo::formatearKg($cargaPendiente).' kg')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning')
                ->url(PedidoResource::getUrl('index', ['tab' => 'pendiente'])),
            Stat::make('En reparto', (string) ($porEstado[EstadoPedido::EnReparto->value] ?? 0))
                ->description($enRuta.' '.($enRuta === 1 ? 'repartidor' : 'repartidores').' en ruta')
                ->descriptionIcon(Heroicon::OutlinedTruck)
                ->color('info')
                ->url(RepartidorResource::getUrl('index')),
            Stat::make('Entregados', (string) ($porEstado[EstadoPedido::Entregado->value] ?? 0))
                ->description('Con comprobante digital')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->url(ComprobanteResource::getUrl('index')),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Comprobantes\ComprobanteResource;
use App\Filament\Resources\Pedidos\PedidoResource;
use App\Filament\Resources\Repartidores\RepartidorResource;
use App\Services\CapacidadVehiculo;
use App\Services\MetricasPanel;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenDelDia extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?bool $isLazyLoaded = false;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array|null
    {
        return ['default' => 1, 'sm' => 2, 'xl' => 4];
    }

    protected function getStats(): array
    {
        $m = MetricasPanel::obtener();
        $semana = collect($m['semana']);
        $entregados = $semana->sum('entregados');
        $fallidos = $semana->sum('no_entregados');
        $cerrados = $entregados + $fallidos;

        return [
            Stat::make('Pedidos de hoy', (string) $m['hoy_total'])
                ->description($m['hoy_total'] === 0
                    ? 'Sin pedidos programados para hoy'
                    : "{$m['hoy_entregados']} de {$m['hoy_total']} entregados")
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->chart($semana->pluck('total')->all())
                ->color('primary')
                ->url(PedidoResource::getUrl('index')),

            Stat::make('Por asignar', (string) $m['pendientes'])
                ->description(match (true) {
                    $m['pendientes'] === 0 => 'Todo asignado',
                    $m['atrasados'] > 0 => CapacidadVehiculo::formatearKg($m['peso_pendiente'])." kg · {$m['atrasados']} atrasados",
                    default => CapacidadVehiculo::formatearKg($m['peso_pendiente']).' kg por asignar',
                })
                ->descriptionIcon($m['atrasados'] > 0 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedClock)
                ->color(match (true) {
                    $m['atrasados'] > 0 => 'danger',
                    $m['pendientes'] > 0 => 'warning',
                    default => 'success',
                })
                ->url(PedidoResource::getUrl('index', ['tab' => 'pendiente'])),

            Stat::make('En reparto', (string) $m['en_reparto'])
                ->description($m['en_ruta'] === 1 ? '1 repartidor en ruta' : "{$m['en_ruta']} repartidores en ruta")
                ->descriptionIcon(Heroicon::OutlinedTruck)
                ->color('info')
                ->url(RepartidorResource::getUrl('index')),

            Stat::make('Tasa de entrega · 7 días', $cerrados === 0 ? '—' : round($entregados / $cerrados * 100).' %')
                ->description($cerrados === 0
                    ? 'Aún sin entregas cerradas'
                    : "{$entregados} entregadas · {$fallidos} fallidas")
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->chart($semana->pluck('entregados')->all())
                ->color('success')
                ->url(ComprobanteResource::getUrl('index')),
        ];
    }
}

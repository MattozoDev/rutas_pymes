<?php

namespace App\Filament\Widgets;

use App\Models\TipoVehiculo;
use App\Models\Vehiculo;
use App\Services\CapacidadVehiculo;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenFlota extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $vehiculos = Vehiculo::query()->get(['tipo', 'capacidad_kg', 'activo'])->groupBy('tipo');

        return CapacidadVehiculo::tiposDeVehiculo()
            ->values()
            ->map(function (TipoVehiculo $tipo) use ($vehiculos): Stat {
                $delTipo = $vehiculos->get($tipo->codigo, collect());
                $inactivos = $delTipo->where('activo', false)->count();
                $capacidadMaxima = $delTipo->max('capacidad_kg') ?? $tipo->capacidad_kg_defecto;

                $descripcion = 'Hasta '.CapacidadVehiculo::formatearKg($capacidadMaxima).' kg'
                    .($delTipo->count() > 1 ? ' c/u' : '')
                    .($inactivos > 0 ? " · {$inactivos} inactivo(s)" : '');

                return Stat::make($tipo->nombre, (string) $delTipo->where('activo', true)->count())
                    ->description($descripcion)
                    ->color($inactivos > 0 ? 'warning' : 'gray');
            })
            ->all();
    }
}

<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoEntrega;
use App\Enums\EstadoRepartidor;
use App\Filament\Resources\Repartidores\RepartidorResource;
use App\Models\Repartidor;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EquipoDeHoy extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?bool $isLazyLoaded = false;

    protected string $view = 'filament.widgets.equipo-de-hoy';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $hoy = today(config('app.zona_horaria_negocio'))->toDateString();
        $deHoy = fn (Builder $query) => $query->whereHas('ruta', fn (Builder $ruta) => $ruta->where('fecha', $hoy));

        $repartidores = Repartidor::query()
            ->with('vehiculo')
            ->where('estado', '!=', EstadoRepartidor::Inactivo)
            ->withCount([
                'entregas as entregas_hoy' => $deHoy,
                'entregas as cerradas_hoy' => fn (Builder $query) => $deHoy($query)
                    ->whereIn('estado', [EstadoEntrega::Entregada, EstadoEntrega::NoEntregada]),
            ])
            ->orderByRaw("case estado when 'en_ruta' then 0 else 1 end")
            ->orderByDesc('entregas_hoy')
            ->orderBy('nombre')
            ->get();

        return [
            'repartidores' => $repartidores->map(fn (Repartidor $repartidor): array => [
                'nombre' => $repartidor->nombre,
                'iniciales' => Str::of($repartidor->nombre)->explode(' ')->filter()->take(2)->map(fn (string $parte): string => mb_strtoupper(mb_substr($parte, 0, 1)))->implode(''),
                'vehiculo' => $repartidor->vehiculo?->etiqueta() ?? 'Sin vehículo asignado',
                'estado' => $repartidor->estado,
                'total' => (int) $repartidor->entregas_hoy,
                'cerradas' => (int) $repartidor->cerradas_hoy,
                'url' => RepartidorResource::getUrl('edit', ['record' => $repartidor]),
            ]),
            'urlFlota' => RepartidorResource::getUrl('index'),
        ];
    }
}

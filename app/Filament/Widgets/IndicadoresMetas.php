<?php

namespace App\Filament\Widgets;

use App\Models\MedicionPlanificacion;
use App\Models\Ruta;
use App\Services\IndicadoresPlanificacion;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IndicadoresMetas extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Metas del proyecto vs. mediciones reales';

    protected function getStats(): array
    {
        $resultado = IndicadoresPlanificacion::calcular(
            MedicionPlanificacion::query()
                ->get(['escenario', 'n_pedidos', 'tiempo_planificacion_min', 'km_motorizados'])
                ->map(fn (MedicionPlanificacion $medicion): array => $medicion->only(['escenario', 'n_pedidos', 'tiempo_planificacion_min', 'km_motorizados'])),
        );

        $rutas = Ruta::query()->whereNotNull('distancia_km')->get(['distancia_km', 'trayecto']);
        $kmIngresado = $rutas->sum(fn (Ruta $ruta): float => (float) ($ruta->trayecto['distancia_orden_ingresado_km'] ?? $ruta->distancia_km));
        $kmOptimizado = $rutas->sum(fn (Ruta $ruta): float => (float) $ruta->distancia_km);
        $ahorroSistema = $kmIngresado > 0 ? round(($kmIngresado - $kmOptimizado) / $kmIngresado * 100, 1) : null;

        return [
            $this->stat(
                'Reducción tiempo de planificación',
                $resultado->reduccionTiempoPorcentaje,
                $resultado->cumpleMetaTiempo(),
                IndicadoresPlanificacion::META_REDUCCION_TIEMPO,
                sprintf(
                    'Manual %s vs. sistema %s min/pedido',
                    $this->numero($resultado->minutosPorPedidoManual),
                    $this->numero($resultado->minutosPorPedidoSistema),
                ),
            ),
            $this->stat(
                'Reducción km motorizados',
                $resultado->reduccionKmPorcentaje,
                $resultado->cumpleMetaKm(),
                IndicadoresPlanificacion::META_REDUCCION_KM,
                sprintf(
                    'Manual %s vs. sistema %s km/pedido',
                    $this->numero($resultado->kmMotorizadosPorPedidoManual),
                    $this->numero($resultado->kmMotorizadosPorPedidoSistema),
                ),
            ),
            Stat::make('Ahorro estimado por orden de paradas', $ahorroSistema === null ? 'Sin datos' : $this->numero($ahorroSistema).' %')
                ->description(sprintf('%d rutas con coordenadas · línea recta, no reemplaza la medición real', $rutas->count()))
                ->descriptionIcon(Heroicon::OutlinedMap)
                ->color('info'),
            Stat::make('Mediciones registradas', (string) $resultado->mediciones)
                ->description('Se necesita al menos una manual y una con el sistema')
                ->color('gray'),
        ];
    }

    private function stat(string $titulo, ?float $valor, ?bool $cumple, float $meta, string $detalle): Stat
    {
        return Stat::make($titulo, $valor === null ? 'Sin datos' : $this->numero($valor).' %')
            ->description(sprintf('Meta: %s %% · %s · %s', $this->numero($meta), match ($cumple) {
                true => 'cumple',
                false => 'no cumple aún',
                null => 'pendiente de medir',
            }, $detalle))
            ->descriptionIcon(match ($cumple) {
                true => Heroicon::OutlinedCheckCircle,
                false => Heroicon::OutlinedExclamationTriangle,
                null => Heroicon::OutlinedClock,
            })
            ->color(match ($cumple) {
                true => 'success',
                false => 'warning',
                null => 'gray',
            });
    }

    private function numero(?float $valor): string
    {
        return $valor === null ? '—' : number_format($valor, 1, ',', '.');
    }
}

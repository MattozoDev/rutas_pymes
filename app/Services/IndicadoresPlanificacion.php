<?php

namespace App\Services;

use App\Enums\EscenarioMedicion;

final class IndicadoresPlanificacion
{
    public const META_REDUCCION_TIEMPO = 50.0;

    public const META_REDUCCION_KM = 20.0;

    /**
     * @param  iterable<array{escenario: EscenarioMedicion|string, n_pedidos: int|string, tiempo_planificacion_min: float|int|string, km_motorizados?: float|int|string|null}>  $mediciones
     */
    public static function calcular(iterable $mediciones): ResultadoIndicadores
    {
        $vacio = ['pedidos' => 0, 'minutos' => 0.0, 'pedidos_con_km' => 0, 'km' => 0.0];
        $acumulado = [
            EscenarioMedicion::Manual->value => $vacio,
            EscenarioMedicion::Sistema->value => $vacio,
        ];
        $total = 0;

        foreach ($mediciones as $medicion) {
            $escenario = $medicion['escenario'] instanceof EscenarioMedicion
                ? $medicion['escenario']->value
                : (string) $medicion['escenario'];
            $pedidos = (int) $medicion['n_pedidos'];

            if ($pedidos <= 0 || ! isset($acumulado[$escenario])) {
                continue;
            }

            $total++;
            $acumulado[$escenario]['pedidos'] += $pedidos;
            $acumulado[$escenario]['minutos'] += (float) $medicion['tiempo_planificacion_min'];

            $km = $medicion['km_motorizados'] ?? null;

            if ($km !== null && $km !== '') {
                $acumulado[$escenario]['pedidos_con_km'] += $pedidos;
                $acumulado[$escenario]['km'] += (float) $km;
            }
        }

        $manual = $acumulado[EscenarioMedicion::Manual->value];
        $sistema = $acumulado[EscenarioMedicion::Sistema->value];

        $minutosManual = self::porPedido($manual['minutos'], $manual['pedidos']);
        $minutosSistema = self::porPedido($sistema['minutos'], $sistema['pedidos']);
        $kmManual = self::porPedido($manual['km'], $manual['pedidos_con_km']);
        $kmSistema = self::porPedido($sistema['km'], $sistema['pedidos_con_km']);

        return new ResultadoIndicadores(
            minutosPorPedidoManual: $minutosManual,
            minutosPorPedidoSistema: $minutosSistema,
            kmMotorizadosPorPedidoManual: $kmManual,
            kmMotorizadosPorPedidoSistema: $kmSistema,
            reduccionTiempoPorcentaje: self::reduccion($minutosManual, $minutosSistema),
            reduccionKmPorcentaje: self::reduccion($kmManual, $kmSistema),
            mediciones: $total,
        );
    }

    private static function porPedido(float $valor, int $pedidos): ?float
    {
        return $pedidos > 0 ? round($valor / $pedidos, 2) : null;
    }

    private static function reduccion(?float $antes, ?float $despues): ?float
    {
        if ($antes === null || $despues === null || $antes <= 0) {
            return null;
        }

        return round(($antes - $despues) / $antes * 100, 1);
    }
}

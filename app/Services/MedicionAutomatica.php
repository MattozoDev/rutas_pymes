<?php

namespace App\Services;

use App\Enums\EscenarioMedicion;
use App\Models\MedicionPlanificacion;
use App\Models\Ruta;

final class MedicionAutomatica
{
    public const GRUPO = 'automatico';

    private const MAXIMO_MINUTOS = 180;

    public static function registrarPlanificacion(Ruta $ruta, int $pedidos, float $minutos, ?string $tipoVehiculo): ?MedicionPlanificacion
    {
        if ($pedidos < 1 || $minutos < 0 || $minutos > self::MAXIMO_MINUTOS) {
            return null;
        }

        $porCalles = ($ruta->trayecto['calculo'] ?? null) === 'calles';
        $kilometros = $ruta->distancia_km !== null ? (float) $ruta->distancia_km : null;

        $medicion = new MedicionPlanificacion([
            'fecha' => $ruta->fecha->toDateString(),
            'escenario' => EscenarioMedicion::Sistema,
            'grupo_comparacion' => self::GRUPO,
            'n_pedidos' => $pedidos,
            'tipo_vehiculo' => $tipoVehiculo,
            'tiempo_planificacion_min' => round($minutos, 2),
            'km_recorridos' => $porCalles ? $kilometros : null,
            'km_motorizados' => $porCalles ? ($tipoVehiculo === 'bicicleta' ? 0 : $kilometros) : null,
            'notas' => 'Registrada automáticamente al asignar la ruta'.($porCalles ? ' (km por calles).' : ' (sin km: faltan coordenadas o no respondió el servicio de rutas).'),
        ]);
        $medicion->registrado_por = auth()->id();
        $medicion->save();

        return $medicion;
    }
}

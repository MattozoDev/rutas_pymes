<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final class RuteoCalles
{
    /**
     * @param  list<array{lat: float, lng: float}>  $puntos
     * @return array{distancia_km: float, duracion_min: int}|null
     */
    public static function calcular(array $puntos, ?string $tipoVehiculo): ?array
    {
        if (count($puntos) < 2) {
            return null;
        }

        $servicio = $tipoVehiculo === 'bicicleta' ? 'routed-bike' : 'routed-car';
        $coordenadas = implode(';', array_map(
            fn (array $punto): string => sprintf('%.6F,%.6F', $punto['lng'], $punto['lat']),
            $puntos,
        ));

        return Cache::store('file')->remember(
            'ruteo.'.md5($servicio.$coordenadas),
            now()->addDay(),
            function () use ($servicio, $coordenadas): ?array {
                try {
                    $respuesta = Http::timeout(config('services.osrm.timeout'))
                        ->acceptJson()
                        ->get(rtrim(config('services.osrm.url'), '/')."/{$servicio}/route/v1/driving/{$coordenadas}", [
                            'overview' => 'false',
                            'steps' => 'false',
                        ]);
                } catch (Throwable $excepcion) {
                    report($excepcion);

                    return null;
                }

                $ruta = $respuesta->json('routes.0');

                if (! $respuesta->successful() || $respuesta->json('code') !== 'Ok' || ! is_array($ruta)) {
                    return null;
                }

                return [
                    'distancia_km' => round((float) $ruta['distance'] / 1000, 2),
                    'duracion_min' => (int) ceil((float) $ruta['duration'] / 60),
                ];
            },
        );
    }
}

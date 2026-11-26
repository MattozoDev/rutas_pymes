<?php

namespace App\Services;

final class PlanificadorRuta
{
    private const RADIO_TIERRA_KM = 6371.0;

    /**
     * @var array<string, int>
     */
    private const VELOCIDAD_KMH = [
        'bicicleta' => 14,
        'torito' => 20,
        'auto' => 24,
        'furgon' => 22,
    ];

    public const MINUTOS_POR_PARADA = 5;

    /**
     * @param  array{lat: float, lng: float}|null  $origen
     * @param  array<string, array{lat: float|null, lng: float|null}>  $paradas
     * @return array{orden: list<string>, distancia_km: float|null}
     */
    public static function ordenarPorVecinoMasCercano(?array $origen, array $paradas): array
    {
        $conCoordenadas = array_filter($paradas, fn (array $parada): bool => $parada['lat'] !== null && $parada['lng'] !== null);
        $sinCoordenadas = array_map('strval', array_keys(array_diff_key($paradas, $conCoordenadas)));

        $orden = [];
        $actual = $origen;
        $pendientes = $conCoordenadas;

        if ($actual === null && $pendientes !== []) {
            $primera = array_key_first($pendientes);
            $orden[] = (string) $primera;
            $actual = $pendientes[$primera];
            unset($pendientes[$primera]);
        }

        while ($pendientes !== []) {
            $masCercana = null;
            $menorDistancia = INF;

            foreach ($pendientes as $id => $parada) {
                $distancia = self::distanciaKm($actual['lat'], $actual['lng'], $parada['lat'], $parada['lng']);

                if ($distancia < $menorDistancia) {
                    $menorDistancia = $distancia;
                    $masCercana = $id;
                }
            }

            $orden[] = (string) $masCercana;
            $actual = $pendientes[$masCercana];
            unset($pendientes[$masCercana]);
        }

        $orden = [...$orden, ...$sinCoordenadas];

        return [
            'orden' => $orden,
            'distancia_km' => $sinCoordenadas === [] && $conCoordenadas !== []
                ? self::distanciaRecorrido($origen, $orden, $paradas)
                : null,
        ];
    }

    /**
     * @param  array{lat: float, lng: float}|null  $origen
     * @param  list<string>  $orden
     * @param  array<string, array{lat: float|null, lng: float|null}>  $paradas
     */
    public static function distanciaRecorrido(?array $origen, array $orden, array $paradas): float
    {
        $total = 0.0;
        $anterior = $origen;

        foreach ($orden as $id) {
            $parada = $paradas[$id];

            if ($anterior !== null) {
                $total += self::distanciaKm($anterior['lat'], $anterior['lng'], $parada['lat'], $parada['lng']);
            }

            $anterior = $parada;
        }

        return round($total, 2);
    }

    public static function distanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $diferenciaLat = deg2rad($lat2 - $lat1);
        $diferenciaLng = deg2rad($lng2 - $lng1);

        $a = sin($diferenciaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($diferenciaLng / 2) ** 2;

        return self::RADIO_TIERRA_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @param  list<string>  $destinos
     */
    public static function urlGoogleMaps(array $destinos, ?string $origen = null, ?string $tipoVehiculo = null): ?string
    {
        if ($destinos === []) {
            return null;
        }

        $parametros = [
            'api' => 1,
            'destination' => end($destinos),
            'travelmode' => $tipoVehiculo === 'bicicleta' ? 'bicycling' : 'driving',
        ];

        if (filled($origen)) {
            $parametros['origin'] = $origen;
        }

        $intermedios = array_slice($destinos, 0, -1);

        if ($intermedios !== []) {
            $parametros['waypoints'] = implode('|', $intermedios);
        }

        return 'https://www.google.com/maps/dir/?'.http_build_query($parametros, '', '&', PHP_QUERY_RFC3986);
    }

    public static function estimarDuracionMin(float $distanciaKm, int $paradas, ?string $tipoVehiculo): int
    {
        $velocidad = self::VELOCIDAD_KMH[$tipoVehiculo] ?? 20;

        return (int) ceil($distanciaKm / $velocidad * 60) + $paradas * self::MINUTOS_POR_PARADA;
    }
}

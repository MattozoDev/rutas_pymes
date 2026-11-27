<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final class Geocodificador
{
    /**
     * @return array{lat: float, lng: float, etiqueta: string}|null
     */
    public static function buscar(?string $direccion, ?string $comuna = null): ?array
    {
        $direccion = trim((string) $direccion);

        if ($direccion === '') {
            return null;
        }

        $consulta = implode(', ', array_filter([$direccion, trim((string) $comuna), 'Región Metropolitana', 'Chile']));

        return Cache::store(config('services.osrm.cache_store'))->remember(
            'geocodificacion.'.md5(mb_strtolower($consulta)),
            now()->addDays(30),
            function () use ($consulta): ?array {
                try {
                    $respuesta = Http::timeout(config('services.nominatim.timeout'))
                        ->withUserAgent(config('services.nominatim.user_agent'))
                        ->acceptJson()
                        ->get(rtrim(config('services.nominatim.url'), '/').'/search', [
                            'q' => $consulta,
                            'format' => 'jsonv2',
                            'countrycodes' => 'cl',
                            'limit' => 1,
                            'accept-language' => 'es',
                        ]);
                } catch (Throwable $excepcion) {
                    report($excepcion);

                    return null;
                }

                $resultado = $respuesta->successful() ? $respuesta->json('0') : null;

                if (! is_array($resultado) || ! isset($resultado['lat'], $resultado['lon'])) {
                    return null;
                }

                return [
                    'lat' => round((float) $resultado['lat'], 6),
                    'lng' => round((float) $resultado['lon'], 6),
                    'etiqueta' => (string) ($resultado['display_name'] ?? ''),
                ];
            },
        );
    }
}

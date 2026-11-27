<?php

namespace Tests\Feature;

use App\Services\RuteoCalles;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RuteoCallesTest extends TestCase
{
    /**
     * @var list<array{lat: float, lng: float}>
     */
    private const PUNTOS = [
        ['lat' => -33.4598, 'lng' => -70.6460],
        ['lat' => -33.4314, 'lng' => -70.6093],
    ];

    public function test_devuelve_km_y_minutos_por_calles(): void
    {
        Http::fake(['*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 7412.6, 'duration' => 701.2]]])]);

        $resultado = RuteoCalles::calcular(self::PUNTOS, 'auto');

        $this->assertSame(['distancia_km' => 7.41, 'duracion_min' => 12], $resultado);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/routed-car/route/v1/driving/-70.646000,-33.459800;-70.609300,-33.431400'));
    }

    public function test_la_bicicleta_usa_el_perfil_de_bicicleta(): void
    {
        Http::fake(['*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 6300, 'duration' => 1740]]])]);

        RuteoCalles::calcular(self::PUNTOS, 'bicicleta');

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/routed-bike/'));
    }

    public function test_devuelve_null_si_el_servicio_falla_o_no_hay_ruta(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['code' => 'NoRoute', 'routes' => []])
            ->push('error', 503)]);

        $this->assertNull(RuteoCalles::calcular(self::PUNTOS, 'auto'));
        $this->assertNull(RuteoCalles::calcular(self::PUNTOS, 'auto'));
    }

    public function test_reutiliza_el_resultado_guardado_en_cache(): void
    {
        Http::fake(['*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 1000, 'duration' => 120]]])]);

        RuteoCalles::calcular(self::PUNTOS, 'auto');
        RuteoCalles::calcular(self::PUNTOS, 'auto');

        Http::assertSentCount(1);
    }

    public function test_no_consulta_con_menos_de_dos_puntos(): void
    {
        Http::fake();

        $this->assertNull(RuteoCalles::calcular([self::PUNTOS[0]], 'auto'));
        Http::assertNothingSent();
    }
}

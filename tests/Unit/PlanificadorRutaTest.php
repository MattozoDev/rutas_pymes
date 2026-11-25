<?php

namespace Tests\Unit;

use App\Services\PlanificadorRuta;
use PHPUnit\Framework\TestCase;

class PlanificadorRutaTest extends TestCase
{
    public function test_distancia_conocida_entre_dos_puntos_de_santiago(): void
    {
        $distancia = PlanificadorRuta::distanciaKm(-33.4378, -70.6505, -33.4569, -70.5973);

        $this->assertEqualsWithDelta(5.3, $distancia, 0.3);
    }

    public function test_ordena_por_vecino_mas_cercano_desde_el_origen(): void
    {
        $origen = ['lat' => 0.0, 'lng' => 0.0];
        $paradas = [
            'lejos' => ['lat' => 0.0, 'lng' => 0.03],
            'cerca' => ['lat' => 0.0, 'lng' => 0.01],
            'medio' => ['lat' => 0.0, 'lng' => 0.02],
        ];

        $resultado = PlanificadorRuta::ordenarPorVecinoMasCercano($origen, $paradas);

        $this->assertSame(['cerca', 'medio', 'lejos'], $resultado['orden']);
        $this->assertEqualsWithDelta(3.34, $resultado['distancia_km'], 0.02);
    }

    public function test_el_orden_optimizado_no_es_mas_largo_que_el_ingresado(): void
    {
        $origen = ['lat' => 0.0, 'lng' => 0.0];
        $paradas = [
            'a' => ['lat' => 0.0, 'lng' => 0.03],
            'b' => ['lat' => 0.0, 'lng' => 0.01],
            'c' => ['lat' => 0.0, 'lng' => 0.02],
        ];

        $ingresado = PlanificadorRuta::distanciaRecorrido($origen, ['a', 'b', 'c'], $paradas);
        $optimizado = PlanificadorRuta::ordenarPorVecinoMasCercano($origen, $paradas)['distancia_km'];

        $this->assertLessThan($ingresado, $optimizado);
    }

    public function test_paradas_sin_coordenadas_van_al_final_y_no_hay_distancia(): void
    {
        $resultado = PlanificadorRuta::ordenarPorVecinoMasCercano(null, [
            'sin' => ['lat' => null, 'lng' => null],
            'a' => ['lat' => 0.0, 'lng' => 0.0],
            'b' => ['lat' => 0.0, 'lng' => 0.01],
        ]);

        $this->assertSame(['a', 'b', 'sin'], $resultado['orden']);
        $this->assertNull($resultado['distancia_km']);
    }

    public function test_estima_duracion_segun_vehiculo_y_paradas(): void
    {
        $this->assertSame(70, PlanificadorRuta::estimarDuracionMin(14, 2, 'bicicleta'));
        $this->assertSame(60 + 5, PlanificadorRuta::estimarDuracionMin(20, 1, 'torito'));
    }

    public function test_enlace_google_maps_con_paradas_intermedias(): void
    {
        $url = PlanificadorRuta::urlGoogleMaps(['Av. Matta 1240, Santiago', 'Gran Avenida 5410, San Miguel'], null, 'bicicleta');

        $this->assertStringStartsWith('https://www.google.com/maps/dir/?api=1', $url);
        $this->assertStringContainsString('destination=Gran%20Avenida%205410%2C%20San%20Miguel', $url);
        $this->assertStringContainsString('waypoints=Av.%20Matta%201240%2C%20Santiago', $url);
        $this->assertStringContainsString('travelmode=bicycling', $url);
        $this->assertNull(PlanificadorRuta::urlGoogleMaps([]));
    }
}

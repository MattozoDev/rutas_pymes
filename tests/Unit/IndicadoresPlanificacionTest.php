<?php

namespace Tests\Unit;

use App\Enums\EscenarioMedicion;
use App\Services\IndicadoresPlanificacion;
use PHPUnit\Framework\TestCase;

class IndicadoresPlanificacionTest extends TestCase
{
    public function test_calcula_reduccion_por_pedido_y_evalua_las_metas(): void
    {
        $resultado = IndicadoresPlanificacion::calcular([
            ['escenario' => EscenarioMedicion::Manual, 'n_pedidos' => 10, 'tiempo_planificacion_min' => 60, 'km_motorizados' => 50],
            ['escenario' => 'manual', 'n_pedidos' => 10, 'tiempo_planificacion_min' => 40, 'km_motorizados' => 30],
            ['escenario' => EscenarioMedicion::Sistema, 'n_pedidos' => 20, 'tiempo_planificacion_min' => '40.00', 'km_motorizados' => '64.00'],
        ]);

        $this->assertSame(5.0, $resultado->minutosPorPedidoManual);
        $this->assertSame(2.0, $resultado->minutosPorPedidoSistema);
        $this->assertSame(60.0, $resultado->reduccionTiempoPorcentaje);
        $this->assertTrue($resultado->cumpleMetaTiempo());

        $this->assertSame(4.0, $resultado->kmMotorizadosPorPedidoManual);
        $this->assertSame(3.2, $resultado->kmMotorizadosPorPedidoSistema);
        $this->assertSame(20.0, $resultado->reduccionKmPorcentaje);
        $this->assertTrue($resultado->cumpleMetaKm());

        $this->assertSame(3, $resultado->mediciones);
    }

    public function test_informa_cuando_no_se_cumple_la_meta(): void
    {
        $resultado = IndicadoresPlanificacion::calcular([
            ['escenario' => 'manual', 'n_pedidos' => 5, 'tiempo_planificacion_min' => 30, 'km_motorizados' => 40],
            ['escenario' => 'sistema', 'n_pedidos' => 5, 'tiempo_planificacion_min' => 20, 'km_motorizados' => 36],
        ]);

        $this->assertSame(33.3, $resultado->reduccionTiempoPorcentaje);
        $this->assertFalse($resultado->cumpleMetaTiempo());
        $this->assertSame(10.0, $resultado->reduccionKmPorcentaje);
        $this->assertFalse($resultado->cumpleMetaKm());
    }

    public function test_sin_ambos_escenarios_no_hay_comparacion(): void
    {
        $resultado = IndicadoresPlanificacion::calcular([
            ['escenario' => 'manual', 'n_pedidos' => 5, 'tiempo_planificacion_min' => 30],
        ]);

        $this->assertNull($resultado->reduccionTiempoPorcentaje);
        $this->assertNull($resultado->cumpleMetaTiempo());
        $this->assertNull($resultado->reduccionKmPorcentaje);
        $this->assertNull($resultado->cumpleMetaKm());
    }

    public function test_los_km_solo_se_comparan_con_mediciones_que_los_registran(): void
    {
        $resultado = IndicadoresPlanificacion::calcular([
            ['escenario' => 'manual', 'n_pedidos' => 10, 'tiempo_planificacion_min' => 50, 'km_motorizados' => 30],
            ['escenario' => 'manual', 'n_pedidos' => 10, 'tiempo_planificacion_min' => 50, 'km_motorizados' => null],
            ['escenario' => 'sistema', 'n_pedidos' => 10, 'tiempo_planificacion_min' => 20, 'km_motorizados' => 24],
        ]);

        $this->assertSame(3.0, $resultado->kmMotorizadosPorPedidoManual);
        $this->assertSame(20.0, $resultado->reduccionKmPorcentaje);
    }

    public function test_ignora_mediciones_sin_pedidos(): void
    {
        $resultado = IndicadoresPlanificacion::calcular([
            ['escenario' => 'manual', 'n_pedidos' => 0, 'tiempo_planificacion_min' => 30],
        ]);

        $this->assertSame(0, $resultado->mediciones);
    }
}

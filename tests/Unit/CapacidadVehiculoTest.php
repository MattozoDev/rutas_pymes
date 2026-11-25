<?php

namespace Tests\Unit;

use App\Services\CapacidadVehiculo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CapacidadVehiculoTest extends TestCase
{
    /**
     * @var array<string, string>
     */
    private const CAPACIDADES = [
        'bicicleta' => '15.00',
        'torito' => '120.00',
        'auto' => '300.00',
        'furgon' => '800.00',
    ];

    public function test_suma_todos_los_pedidos_de_la_ruta_contra_la_capacidad(): void
    {
        $evaluacion = CapacidadVehiculo::evaluar('300.00', ['18.00', '12.00']);

        $this->assertSame(30.0, $evaluacion->cargaKg);
        $this->assertFalse($evaluacion->excede());
        $this->assertSame(10, $evaluacion->porcentaje());
        $this->assertSame(270.0, $evaluacion->disponibleKg());
        $this->assertSame('Capacidad suficiente · 30 / 300 kg (10 %)', $evaluacion->mensaje());
    }

    public function test_cada_pedido_cabe_pero_la_suma_excede(): void
    {
        $evaluacion = CapacidadVehiculo::evaluar(120, [40, 36, 50]);

        $this->assertTrue($evaluacion->excede());
        $this->assertSame(105, $evaluacion->porcentaje());
        $this->assertSame(0.0, $evaluacion->disponibleKg());
        $this->assertStringStartsWith('Capacidad excedida', $evaluacion->mensaje());
    }

    public function test_carga_igual_a_la_capacidad_se_permite(): void
    {
        $this->assertFalse(CapacidadVehiculo::evaluar(15, [7.5, 7.5])->excede());
        $this->assertTrue(CapacidadVehiculo::evaluar(15, [7.5, 7.51])->excede());
    }

    public function test_vehiculo_sin_capacidad_no_acepta_carga(): void
    {
        $this->assertTrue(CapacidadVehiculo::evaluar(0, [1])->excede());
        $this->assertSame(100, CapacidadVehiculo::evaluar(0, [1])->porcentaje());
    }

    /**
     * @return array<string, array{float, string|null}>
     */
    public static function pesosYVehiculoMinimo(): array
    {
        return [
            'pedido liviano va en bicicleta' => [3.0, 'bicicleta'],
            'justo en el límite de la bicicleta' => [15.0, 'bicicleta'],
            '18 kg ya no cabe en bicicleta' => [18.0, 'torito'],
            'carga mediana requiere auto' => [160.0, 'auto'],
            'carga grande requiere furgón' => [500.0, 'furgon'],
            'ningún vehículo soporta la carga' => [900.0, null],
        ];
    }

    #[DataProvider('pesosYVehiculoMinimo')]
    public function test_vehiculo_minimo_para_un_pedido(float $peso, ?string $esperado): void
    {
        $this->assertSame($esperado, CapacidadVehiculo::tipoMinimo($peso, self::CAPACIDADES));
    }

    public function test_tipos_aptos_ordenados_de_menor_a_mayor(): void
    {
        $this->assertSame(['auto', 'furgon'], CapacidadVehiculo::tiposAptos(160, array_reverse(self::CAPACIDADES, true)));
    }

    public function test_formatea_kilos_sin_decimales_innecesarios(): void
    {
        $this->assertSame('30', CapacidadVehiculo::formatearKg('30.00'));
        $this->assertSame('12,50', CapacidadVehiculo::formatearKg(12.5));
        $this->assertSame('1.200', CapacidadVehiculo::formatearKg(1200));
    }
}

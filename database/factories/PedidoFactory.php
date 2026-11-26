<?php

namespace Database\Factories;

use App\Enums\EstadoPedido;
use App\Enums\TamanoCarga;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pedido>
 */
class PedidoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $direccion = DatosChilenos::direccion();

        return [
            'empresa_id' => Empresa::factory(),
            'cliente_id' => fn (array $attributes) => Cliente::factory()->state(['empresa_id' => $attributes['empresa_id']]),
            'fecha_entrega' => now()->toDateString(),
            'direccion_entrega' => $direccion['direccion'],
            'comuna' => $direccion['comuna'],
            'referencia' => DatosChilenos::referencia(),
            'latitud' => $direccion['latitud'],
            'longitud' => $direccion['longitud'],
            'bultos' => fake()->numberBetween(1, 4),
            'peso_kg' => fake()->randomFloat(2, 1, 40),
            'tamano' => TamanoCarga::Mediano,
            'estado' => EstadoPedido::Pendiente,
        ];
    }

    public function asignado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoPedido::Asignado,
        ]);
    }
}

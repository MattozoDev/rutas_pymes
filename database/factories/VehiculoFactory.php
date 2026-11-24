<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehiculo>
 */
class VehiculoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'tipo' => 'auto',
            'identificador' => strtoupper(fake()->unique()->bothify('??##??')),
            'capacidad_kg' => 300,
            'activo' => true,
        ];
    }

    public function bicicleta(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'bicicleta',
            'capacidad_kg' => 15,
        ]);
    }
}

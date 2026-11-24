<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => fake()->name(),
            'telefono' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'direccion' => fake()->streetAddress(),
            'comuna' => fake()->city(),
            'latitud' => fake()->latitude(-33.6, -33.3),
            'longitud' => fake()->longitude(-70.8, -70.5),
            'activo' => true,
        ];
    }
}

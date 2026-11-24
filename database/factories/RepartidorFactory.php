<?php

namespace Database\Factories;

use App\Enums\EstadoRepartidor;
use App\Models\Empresa;
use App\Models\Repartidor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repartidor>
 */
class RepartidorFactory extends Factory
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
            'estado' => EstadoRepartidor::Disponible,
        ];
    }

    public function conUsuario(): static
    {
        return $this->state([
            'user_id' => fn (array $attributes) => User::factory()->repartidor()->state(['empresa_id' => $attributes['empresa_id']]),
        ]);
    }
}

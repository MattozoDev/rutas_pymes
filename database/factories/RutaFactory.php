<?php

namespace Database\Factories;

use App\Enums\EstadoRuta;
use App\Enums\OrigenRuta;
use App\Models\Empresa;
use App\Models\Repartidor;
use App\Models\Ruta;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ruta>
 */
class RutaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'repartidor_id' => fn (array $attributes) => Repartidor::factory()->state(['empresa_id' => $attributes['empresa_id']]),
            'vehiculo_id' => fn (array $attributes) => Vehiculo::factory()->state(['empresa_id' => $attributes['empresa_id']]),
            'fecha' => now()->toDateString(),
            'estado' => EstadoRuta::Planificada,
            'origen' => OrigenRuta::Manual,
        ];
    }

    public function enCurso(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoRuta::EnCurso,
        ]);
    }
}

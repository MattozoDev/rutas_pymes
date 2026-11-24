<?php

namespace Database\Factories;

use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\Pedido;
use App\Models\Ruta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entrega>
 */
class EntregaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ruta_id' => Ruta::factory(),
            'empresa_id' => fn (array $attributes) => Ruta::query()->withoutGlobalScopes()->find($attributes['ruta_id'])->empresa_id,
            'repartidor_id' => fn (array $attributes) => Ruta::query()->withoutGlobalScopes()->find($attributes['ruta_id'])->repartidor_id,
            'vehiculo_id' => fn (array $attributes) => Ruta::query()->withoutGlobalScopes()->find($attributes['ruta_id'])->vehiculo_id,
            'pedido_id' => fn (array $attributes) => Pedido::factory()->asignado()->state(['empresa_id' => $attributes['empresa_id']]),
            'orden_parada' => 1,
            'estado' => EstadoEntrega::Asignada,
        ];
    }
}

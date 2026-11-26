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
        $direccion = DatosChilenos::direccion();

        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => DatosChilenos::nombreCliente(),
            'telefono' => DatosChilenos::celular(),
            'email' => fake()->unique()->safeEmail(),
            'direccion' => $direccion['direccion'],
            'comuna' => $direccion['comuna'],
            'latitud' => $direccion['latitud'],
            'longitud' => $direccion['longitud'],
            'activo' => true,
        ];
    }
}

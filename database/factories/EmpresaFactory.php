<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $direccion = DatosChilenos::direccion();

        return [
            'nombre' => 'Almacén '.fake()->randomElement(['Don Pedro', 'La Esquina', 'El Roble', 'San Juan']),
            'rut' => DatosChilenos::rut(),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => DatosChilenos::telefonoFijo(),
            'direccion' => $direccion['direccion'],
            'comuna' => $direccion['comuna'],
            'latitud' => $direccion['latitud'],
            'longitud' => $direccion['longitud'],
            'activa' => true,
        ];
    }
}

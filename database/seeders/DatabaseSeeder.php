<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Pedido;
use App\Models\Repartidor;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::factory()->create(['nombre' => 'PYME Demo']);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('prueba'),
            'empresa_id' => $empresa->id,
        ]);

        $bicicleta = Vehiculo::factory()->bicicleta()->create(['empresa_id' => $empresa->id, 'identificador' => 'BICI-01']);
        $auto = Vehiculo::factory()->create(['empresa_id' => $empresa->id, 'identificador' => 'AUTO-01']);

        Repartidor::factory()->conUsuario()->create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Repartidor Demo',
            'vehiculo_id' => $bicicleta->id,
        ])->usuario->update(['email' => 'repartidor@example.com', 'password' => 'prueba']);

        Repartidor::factory()->create(['empresa_id' => $empresa->id, 'vehiculo_id' => $auto->id]);

        Cliente::factory()->count(5)->create(['empresa_id' => $empresa->id])
            ->each(fn (Cliente $cliente) => Pedido::factory()->count(2)->create([
                'empresa_id' => $empresa->id,
                'cliente_id' => $cliente->id,
                'fecha_entrega' => now(config('app.zona_horaria_negocio'))->toDateString(),
            ]));
    }
}

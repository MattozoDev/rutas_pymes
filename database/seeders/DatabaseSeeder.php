<?php

namespace Database\Seeders;

use App\Enums\EstadoRepartidor;
use App\Enums\RolUsuario;
use App\Enums\TamanoCarga;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Pedido;
use App\Models\Repartidor;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Factories\DatosChilenos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    private const CONTRASENA = 'prueba';

    public function run(): void
    {
        $empresa = Empresa::query()->firstOrCreate(
            ['rut' => DatosChilenos::rut(76_543_210)],
            [
                'nombre' => 'Almacén Don Pedro',
                'email' => 'contacto@almacendonpedro.test',
                'telefono' => '+56 2 2555 0100',
                'direccion' => 'Av. Matta 1100',
                'comuna' => 'Santiago',
                'latitud' => -33.459800,
                'longitud' => -70.646000,
                'activa' => true,
            ],
        );

        User::query()->updateOrCreate(['email' => 'admin@almacendonpedro.test'], [
            'name' => 'Pedro González',
            'password' => self::CONTRASENA,
            'empresa_id' => $empresa->id,
            'rol' => RolUsuario::Administrador,
            'telefono' => '+56 9 5555 0100',
            'activo' => true,
        ]);

        $vehiculos = $this->crearFlota($empresa);
        $this->crearRepartidores($empresa, $vehiculos);
        $this->crearClientesYPedidos($empresa);
    }

    /**
     * @return array<string, Vehiculo>
     */
    private function crearFlota(Empresa $empresa): array
    {
        $flota = [
            'BIC-01' => ['tipo' => 'bicicleta', 'capacidad_kg' => 15, 'activo' => true],
            'TOR-01' => ['tipo' => 'torito', 'capacidad_kg' => 120, 'activo' => true],
            'TOR-02' => ['tipo' => 'torito', 'capacidad_kg' => 120, 'activo' => false],
            'AB-CD-12' => ['tipo' => 'auto', 'capacidad_kg' => 300, 'activo' => true],
            'EF-GH-34' => ['tipo' => 'furgon', 'capacidad_kg' => 800, 'activo' => true],
        ];

        $vehiculos = [];

        foreach ($flota as $identificador => $datos) {
            $vehiculo = Vehiculo::query()->firstOrNew(['empresa_id' => $empresa->id, 'identificador' => $identificador]);
            $vehiculo->fill(['identificador' => $identificador, ...$datos]);
            $vehiculo->empresa_id = $empresa->id;
            $vehiculo->save();

            $vehiculos[$identificador] = $vehiculo;
        }

        return $vehiculos;
    }

    /**
     * @param  array<string, Vehiculo>  $vehiculos
     */
    private function crearRepartidores(Empresa $empresa, array $vehiculos): void
    {
        $equipo = [
            ['nombre' => 'Camila Vera', 'telefono' => '+56 9 5555 0101', 'vehiculo' => 'BIC-01', 'estado' => EstadoRepartidor::Disponible, 'email' => 'camila@almacendonpedro.test'],
            ['nombre' => 'Jorge Rojas', 'telefono' => '+56 9 5555 0102', 'vehiculo' => 'TOR-01', 'estado' => EstadoRepartidor::Disponible, 'email' => 'jorge@almacendonpedro.test'],
            ['nombre' => 'Paula Araya', 'telefono' => '+56 9 5555 0103', 'vehiculo' => 'AB-CD-12', 'estado' => EstadoRepartidor::Disponible, 'email' => null],
            ['nombre' => 'Luis Morales', 'telefono' => '+56 9 5555 0104', 'vehiculo' => 'EF-GH-34', 'estado' => EstadoRepartidor::Disponible, 'email' => null],
            ['nombre' => 'Diego Tapia', 'telefono' => '+56 9 5555 0105', 'vehiculo' => null, 'estado' => EstadoRepartidor::Inactivo, 'email' => null],
        ];

        foreach ($equipo as $persona) {
            $usuarioId = null;

            if ($persona['email'] !== null) {
                $usuarioId = User::query()->updateOrCreate(['email' => $persona['email']], [
                    'name' => $persona['nombre'],
                    'password' => self::CONTRASENA,
                    'empresa_id' => $empresa->id,
                    'rol' => RolUsuario::Repartidor,
                    'telefono' => $persona['telefono'],
                    'activo' => true,
                ])->id;
            }

            $repartidor = Repartidor::query()->firstOrNew(['empresa_id' => $empresa->id, 'nombre' => $persona['nombre']]);
            $repartidor->fill([
                'telefono' => $persona['telefono'],
                'vehiculo_id' => $persona['vehiculo'] ? $vehiculos[$persona['vehiculo']]->id : null,
                'estado' => $persona['estado'],
                'user_id' => $usuarioId,
            ]);
            $repartidor->empresa_id = $empresa->id;
            $repartidor->save();
        }
    }

    private function crearClientesYPedidos(Empresa $empresa): void
    {
        $hoy = Carbon::today(config('app.zona_horaria_negocio'));
        $manana = $hoy->copy()->addDay();

        $clientes = [
            ['Carla Muñoz', '+56 9 1234 5678', 'Av. Matta 1240', 'Santiago', null, [$hoy, 2, 18, TamanoCarga::Mediano, '09:00', '13:00', 'Cajas medianas']],
            ['Minimarket El Sol', '+56 2 2345 6789', 'Los Aromos 455', 'Ñuñoa', 'Local 2', [$hoy, 4, 40, TamanoCarga::Grande, '08:00', '11:00', 'Bebidas y abarrotes']],
            ['Pedro Soto', '+56 9 2222 3333', 'Av. Irarrázaval 3021', 'Ñuñoa', 'Depto 504', [$hoy, 1, 3, TamanoCarga::Pequeno, null, null, 'Caja pequeña']],
            ['Botillería Central', '+56 9 8765 4321', 'San Diego 980', 'Santiago', null, [$hoy, 3, 36, TamanoCarga::Mediano, '10:00', '14:00', 'Cajas medianas']],
            ['Ana Fuentes', '+56 9 4444 5555', 'Pedro de Valdivia 77', 'Providencia', 'Torre B, depto 1203', [$hoy, 6, 90, TamanoCarga::Grande, '15:00', '19:00', 'Pedido mensual del hogar']],
            ['Panadería La Espiga', '+56 2 2666 7777', 'Gran Avenida José Miguel Carrera 5410', 'San Miguel', null, [$hoy, 1, 12, TamanoCarga::Mediano, '07:00', '09:00', 'Sacos de harina']],
            ['Ferretería Lo Valledor', '+56 2 2888 9999', 'Av. Lo Valledor 1890', 'Pedro Aguirre Cerda', 'Portón de carga', [$manana, 8, 160, TamanoCarga::Grande, '09:00', '12:00', 'Materiales de construcción']],
            ['Almacén Doña Rosa', '+56 9 7777 1212', 'Av. Recoleta 2345', 'Recoleta', null, [$manana, 2, 22, TamanoCarga::Mediano, null, null, 'Abarrotes']],
            ['Javiera Contreras', '+56 9 6123 4455', 'Av. Macul 4100', 'Macul', 'Casa 12', [$manana, 1, 6, TamanoCarga::Pequeno, '18:00', '21:00', null]],
            ['Cafetería Plaza Ñuñoa', '+56 2 2274 1010', 'José Pedro Alessandri 120', 'Ñuñoa', 'Local 4', [$manana, 3, 28, TamanoCarga::Mediano, '07:30', '09:30', 'Café y lácteos']],
            ['Verdulería Los Andes', '+56 9 9876 1122', 'Av. Vicuña Mackenna 7255', 'La Florida', null, [$manana, 5, 75, TamanoCarga::Grande, '06:30', '09:00', 'Frutas y verduras']],
            ['Matías Sepúlveda', '+56 9 5432 1987', 'Av. Pajaritos 3150', 'Maipú', 'Depto 21', [$manana, 1, 4, TamanoCarga::Pequeno, null, null, null]],
        ];

        foreach ($clientes as [$nombre, $telefono, $direccion, $comuna, $referencia, $pedido]) {
            $coordenadas = DatosChilenos::direccion($comuna);

            $cliente = Cliente::query()->firstOrNew(['empresa_id' => $empresa->id, 'nombre' => $nombre]);
            $cliente->fill([
                'telefono' => $telefono,
                'email' => str($nombre)->ascii()->lower()->replace(' ', '.')->append('@correo.test')->toString(),
                'direccion' => $direccion,
                'comuna' => $comuna,
                'latitud' => $coordenadas['latitud'],
                'longitud' => $coordenadas['longitud'],
                'activo' => true,
            ]);
            $cliente->empresa_id = $empresa->id;
            $cliente->save();

            [$fecha, $bultos, $peso, $tamano, $desde, $hasta, $descripcion] = $pedido;

            $existe = Pedido::query()
                ->where('empresa_id', $empresa->id)
                ->where('cliente_id', $cliente->id)
                ->where('fecha_entrega', $fecha->toDateString())
                ->exists();

            if ($existe) {
                continue;
            }

            $nuevo = new Pedido([
                'cliente_id' => $cliente->id,
                'fecha_entrega' => $fecha->toDateString(),
                'franja_inicio' => $desde,
                'franja_fin' => $hasta,
                'direccion_entrega' => $direccion,
                'comuna' => $comuna,
                'referencia' => $referencia,
                'latitud' => $coordenadas['latitud'],
                'longitud' => $coordenadas['longitud'],
                'bultos' => $bultos,
                'peso_kg' => $peso,
                'tamano' => $tamano,
                'descripcion_carga' => $descripcion,
            ]);
            $nuevo->empresa_id = $empresa->id;
            $nuevo->save();
        }
    }
}

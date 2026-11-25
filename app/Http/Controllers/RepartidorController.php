<?php

namespace App\Http\Controllers;

use App\Enums\EstadoRepartidor;
use App\Enums\RolUsuario;
use App\Http\Requests\GuardarRepartidorRequest;
use App\Models\Repartidor;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RepartidorController extends Controller
{
    public function index(): void {}

    public function create(): void {}

    public function store(GuardarRepartidorRequest $request): void
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos, $request): void {
            $repartidor = new Repartidor(Arr::except($datos, ['email', 'password']));

            if (! empty($datos['email']) && ! empty($datos['password'])) {
                $this->asociarUsuarioNuevo($repartidor, $datos, $request->user()->empresa_id);
            }

            $repartidor->save();
        });
    }

    public function show(Repartidor $repartidor): void {}

    public function edit(Repartidor $repartidor): void {}

    public function update(GuardarRepartidorRequest $request, Repartidor $repartidor): void
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos, $repartidor, $request): void {
            $repartidor->fill(Arr::except($datos, ['email', 'password']));

            if ($repartidor->usuario !== null) {
                $repartidor->usuario->fill(array_filter([
                    'name' => $datos['nombre'],
                    'telefono' => $datos['telefono'] ?? null,
                    'email' => $datos['email'] ?? null,
                    'password' => $datos['password'] ?? null,
                ]))->save();
            } elseif (! empty($datos['email']) && ! empty($datos['password'])) {
                $this->asociarUsuarioNuevo($repartidor, $datos, $request->user()->empresa_id);
            }

            $repartidor->save();
        });
    }

    public function destroy(Repartidor $repartidor): void
    {
        DB::transaction(function () use ($repartidor): void {
            if ($repartidor->rutas()->exists() || $repartidor->entregas()->exists()) {
                $repartidor->update(['estado' => EstadoRepartidor::Inactivo]);
                $repartidor->usuario?->update(['activo' => false]);
            } else {
                $usuario = $repartidor->usuario;
                $repartidor->delete();
                $usuario?->delete();
            }
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function asociarUsuarioNuevo(Repartidor $repartidor, array $datos, string $empresaId): void
    {
        $repartidor->user_id = User::create([
            'name' => $datos['nombre'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'telefono' => $datos['telefono'] ?? null,
            'empresa_id' => $empresaId,
            'rol' => RolUsuario::Repartidor,
        ])->id;
    }
}

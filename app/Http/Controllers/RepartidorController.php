<?php

namespace App\Http\Controllers;

use App\Enums\EstadoRepartidor;
use App\Enums\RolUsuario;
use App\Http\Requests\GuardarRepartidorRequest;
use App\Models\Repartidor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RepartidorController extends Controller
{
    public function index(): void {}

    public function create(): void {}

    public function store(GuardarRepartidorRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $repartidor = DB::transaction(function () use ($datos, $request): Repartidor {
            $repartidor = new Repartidor(Arr::except($datos, ['email', 'password']));

            if (! empty($datos['email']) && ! empty($datos['password'])) {
                $repartidor->user_id = $this->crearUsuario($datos, $request->user()->empresa_id)->id;
            }

            $repartidor->save();

            return $repartidor;
        });

        return redirect()->route('repartidores.show', $repartidor)->with('status', 'Repartidor creado.');
    }

    public function show(Repartidor $repartidor): void {}

    public function edit(Repartidor $repartidor): void {}

    public function update(GuardarRepartidorRequest $request, Repartidor $repartidor): RedirectResponse
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
                $repartidor->user_id = $this->crearUsuario($datos, $request->user()->empresa_id)->id;
            }

            $repartidor->save();
        });

        return redirect()->route('repartidores.show', $repartidor)->with('status', 'Repartidor actualizado.');
    }

    public function destroy(Repartidor $repartidor): RedirectResponse
    {
        if ($repartidor->rutas()->exists() || $repartidor->entregas()->exists()) {
            DB::transaction(function () use ($repartidor): void {
                $repartidor->update(['estado' => EstadoRepartidor::Inactivo]);
                $repartidor->usuario?->update(['activo' => false]);
            });

            return redirect()->route('repartidores.index')->with('status', 'El repartidor tiene historial: se desactivó.');
        }

        DB::transaction(function () use ($repartidor): void {
            $usuario = $repartidor->usuario;
            $repartidor->delete();
            $usuario?->delete();
        });

        return redirect()->route('repartidores.index')->with('status', 'Repartidor eliminado.');
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function crearUsuario(array $datos, string $empresaId): User
    {
        return User::create([
            'name' => $datos['nombre'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'telefono' => $datos['telefono'] ?? null,
            'empresa_id' => $empresaId,
            'rol' => RolUsuario::Repartidor,
        ]);
    }
}

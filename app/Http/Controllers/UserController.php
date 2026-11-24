<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request): void {}

    public function create(): void {}

    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'empresa_id' => $request->user()->empresa_id,
        ]);

        return redirect()->route('usuarios.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $usuario): void
    {
        Gate::authorize('update', $usuario);
    }

    public function update(GuardarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        Gate::authorize('update', $usuario);

        $datos = $request->validated();

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('status', 'Usuario actualizado.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        Gate::authorize('delete', $usuario);

        $usuario->update(['activo' => false]);

        return redirect()->route('usuarios.index')->with('status', 'Usuario desactivado.');
    }
}

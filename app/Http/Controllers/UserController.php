<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request): void {}

    public function create(): void {}

    public function store(GuardarUsuarioRequest $request): void
    {
        User::create([
            ...$request->validated(),
            'empresa_id' => $request->user()->empresa_id,
        ]);
    }

    public function edit(User $usuario): void
    {
        Gate::authorize('update', $usuario);
    }

    public function update(GuardarUsuarioRequest $request, User $usuario): void
    {
        Gate::authorize('update', $usuario);

        $datos = $request->validated();

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);
    }

    public function destroy(User $usuario): void
    {
        Gate::authorize('delete', $usuario);

        $usuario->update(['activo' => false]);
    }
}

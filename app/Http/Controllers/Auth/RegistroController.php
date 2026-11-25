<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroRequest;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegistroController extends Controller
{
    public function create(): void {}

    public function store(RegistroRequest $request): void
    {
        DB::transaction(function () use ($request): void {
            $empresa = Empresa::create([
                'nombre' => $request->validated('empresa_nombre'),
                'rut' => $request->validated('empresa_rut'),
                'email' => $request->validated('email'),
            ]);

            $usuario = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'empresa_id' => $empresa->id,
                'rol' => RolUsuario::Administrador,
            ]);

            Auth::login($usuario);
        });

        $request->session()->regenerate();
    }
}

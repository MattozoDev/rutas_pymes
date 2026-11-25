<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SesionController extends Controller
{
    public function create(): void {}

    public function store(Request $request): void
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credenciales['activo'] = true;

        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas o la cuenta está desactivada.',
            ]);
        }

        $request->session()->regenerate();
    }

    public function destroy(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}

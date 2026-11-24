<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InicioController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()->esAdministrador()
            ? redirect()->route('dashboard')
            : redirect()->route('entregas.index');
    }
}

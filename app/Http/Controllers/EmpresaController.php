<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarEmpresaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function edit(Request $request): void {}

    public function update(ActualizarEmpresaRequest $request): RedirectResponse
    {
        $request->user()->empresa->update($request->validated());

        return redirect()->route('empresa.edit')->with('status', 'Datos de la empresa actualizados.');
    }
}

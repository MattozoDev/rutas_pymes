<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarEmpresaRequest;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function edit(Request $request): void {}

    public function update(ActualizarEmpresaRequest $request): void
    {
        $request->user()->empresa->update($request->validated());
    }
}

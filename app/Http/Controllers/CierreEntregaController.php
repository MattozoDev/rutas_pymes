<?php

namespace App\Http\Controllers;

use App\Http\Requests\CerrarEntregaRequest;
use App\Models\Entrega;
use Illuminate\Support\Arr;

class CierreEntregaController extends Controller
{
    public function store(CerrarEntregaRequest $request, Entrega $entrega): void
    {
        $datos = Arr::except($request->validated(), ['evidencia']);

        if ($request->hasFile('evidencia')) {
            $datos['evidencia_path'] = $request->file('evidencia')->store("evidencias/{$entrega->empresa_id}");
        }

        $entrega->update($datos);
    }
}

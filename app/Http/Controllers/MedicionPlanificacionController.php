<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarMedicionRequest;
use App\Models\MedicionPlanificacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MedicionPlanificacionController extends Controller
{
    public function index(Request $request): void {}

    public function create(): void {}

    public function store(GuardarMedicionRequest $request): RedirectResponse
    {
        $medicion = new MedicionPlanificacion($request->validated());
        $medicion->registrado_por = $request->user()->id;
        $medicion->save();

        return redirect()->route('mediciones.index')->with('status', 'Medición registrada.');
    }

    public function destroy(MedicionPlanificacion $medicion): RedirectResponse
    {
        $medicion->delete();

        return redirect()->route('mediciones.index')->with('status', 'Medición eliminada.');
    }
}

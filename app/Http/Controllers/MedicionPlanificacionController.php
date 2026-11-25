<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarMedicionRequest;
use App\Models\MedicionPlanificacion;
use Illuminate\Http\Request;

class MedicionPlanificacionController extends Controller
{
    public function index(Request $request): void {}

    public function create(): void {}

    public function store(GuardarMedicionRequest $request): void
    {
        $medicion = new MedicionPlanificacion($request->validated());
        $medicion->registrado_por = $request->user()->id;
        $medicion->save();
    }

    public function destroy(MedicionPlanificacion $medicion): void
    {
        $medicion->delete();
    }
}

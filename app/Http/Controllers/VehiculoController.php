<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarVehiculoRequest;
use App\Models\Entrega;
use App\Models\TipoVehiculo;
use App\Models\Vehiculo;

class VehiculoController extends Controller
{
    public function index(): void {}

    public function create(): void {}

    public function store(GuardarVehiculoRequest $request): void
    {
        $datos = $request->validated();
        $datos['capacidad_kg'] ??= TipoVehiculo::query()->whereKey($datos['tipo'])->value('capacidad_kg_defecto');

        Vehiculo::create($datos);
    }

    public function edit(Vehiculo $vehiculo): void {}

    public function update(GuardarVehiculoRequest $request, Vehiculo $vehiculo): void
    {
        $datos = $request->validated();
        $datos['capacidad_kg'] ??= TipoVehiculo::query()->whereKey($datos['tipo'])->value('capacidad_kg_defecto');

        $vehiculo->update($datos);
    }

    public function destroy(Vehiculo $vehiculo): void
    {
        if ($vehiculo->rutas()->exists() || Entrega::query()->whereBelongsTo($vehiculo)->exists()) {
            $vehiculo->update(['activo' => false]);
        } else {
            $vehiculo->delete();
        }
    }
}

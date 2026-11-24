<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarVehiculoRequest;
use App\Models\Entrega;
use App\Models\TipoVehiculo;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;

class VehiculoController extends Controller
{
    public function index(): void
    {
        $vehiculos = Vehiculo::query()
            ->with(['tipoVehiculo', 'repartidores'])
            ->orderByDesc('activo')
            ->orderBy('identificador')
            ->get();
    }

    public function create(): void {}

    public function store(GuardarVehiculoRequest $request): RedirectResponse
    {
        Vehiculo::create($this->conCapacidadPorDefecto($request->validated()));

        return redirect()->route('vehiculos.index')->with('status', 'Vehículo creado.');
    }

    public function edit(Vehiculo $vehiculo): void {}

    public function update(GuardarVehiculoRequest $request, Vehiculo $vehiculo): RedirectResponse
    {
        $vehiculo->update($this->conCapacidadPorDefecto($request->validated()));

        return redirect()->route('vehiculos.index')->with('status', 'Vehículo actualizado.');
    }

    public function destroy(Vehiculo $vehiculo): RedirectResponse
    {
        if ($vehiculo->rutas()->exists() || Entrega::query()->whereBelongsTo($vehiculo)->exists()) {
            $vehiculo->update(['activo' => false]);

            return redirect()->route('vehiculos.index')->with('status', 'El vehículo tiene historial: se desactivó.');
        }

        $vehiculo->delete();

        return redirect()->route('vehiculos.index')->with('status', 'Vehículo eliminado.');
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function conCapacidadPorDefecto(array $datos): array
    {
        $datos['capacidad_kg'] ??= TipoVehiculo::query()->whereKey($datos['tipo'])->value('capacidad_kg_defecto');

        return $datos;
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEntrega;
use App\Enums\EstadoRuta;
use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EstadoRutaController extends Controller
{
    public function update(Request $request, Ruta $ruta): void
    {
        Gate::authorize('cambiarEstado', $ruta);

        $nuevoEstado = EstadoRuta::from($request->validate([
            'estado' => ['required', Rule::enum(EstadoRuta::class)->only([
                EstadoRuta::EnCurso, EstadoRuta::Completada, EstadoRuta::Cancelada,
            ])],
        ])['estado']);

        $entregasAbiertas = $ruta->entregas()
            ->whereIn('estado', [EstadoEntrega::Asignada, EstadoEntrega::EnCamino]);

        $puedeCambiar = $ruta->estado->puedeCambiarA($nuevoEstado)
            && ! ($nuevoEstado === EstadoRuta::Completada && $entregasAbiertas->exists());

        if ($puedeCambiar) {
            DB::transaction(function () use ($ruta, $nuevoEstado, $entregasAbiertas): void {
                if ($nuevoEstado === EstadoRuta::Cancelada) {
                    $entregasAbiertas->delete();
                }

                $ruta->estado = $nuevoEstado;
                $ruta->save();
            });
        }
    }
}

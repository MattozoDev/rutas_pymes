<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEntrega;
use App\Enums\EstadoRuta;
use App\Models\Ruta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EstadoRutaController extends Controller
{
    public function update(Request $request, Ruta $ruta): RedirectResponse
    {
        Gate::authorize('cambiarEstado', $ruta);

        $nuevoEstado = EstadoRuta::from($request->validate([
            'estado' => ['required', Rule::enum(EstadoRuta::class)->only([
                EstadoRuta::EnCurso, EstadoRuta::Completada, EstadoRuta::Cancelada,
            ])],
        ])['estado']);

        if (! $ruta->estado->puedeCambiarA($nuevoEstado)) {
            return back()->withErrors(['estado' => "No se puede pasar de {$ruta->estado->value} a {$nuevoEstado->value}."]);
        }

        $entregasAbiertas = $ruta->entregas()
            ->whereIn('estado', [EstadoEntrega::Asignada, EstadoEntrega::EnCamino]);

        if ($nuevoEstado === EstadoRuta::Completada && $entregasAbiertas->exists()) {
            return back()->withErrors(['estado' => 'Aún hay entregas sin cerrar en esta ruta.']);
        }

        DB::transaction(function () use ($ruta, $nuevoEstado, $entregasAbiertas): void {
            if ($nuevoEstado === EstadoRuta::Cancelada) {
                $entregasAbiertas->delete();
            }

            $ruta->estado = $nuevoEstado;
            $ruta->save();
        });

        return back()->with('status', 'Estado de la ruta actualizado.');
    }
}

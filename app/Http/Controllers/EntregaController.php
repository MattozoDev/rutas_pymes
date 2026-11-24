<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\Repartidor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EntregaController extends Controller
{
    public function index(Request $request): void
    {
        $this->repartidorActual($request);
    }

    public function show(Entrega $entrega): void
    {
        Gate::authorize('view', $entrega);
    }

    public function update(Entrega $entrega): RedirectResponse
    {
        Gate::authorize('update', $entrega);

        if ($entrega->estado !== EstadoEntrega::Asignada) {
            return back()->withErrors(['estado' => 'La entrega ya está en camino.']);
        }

        $entrega->update(['estado' => EstadoEntrega::EnCamino]);

        return back()->with('status', 'Entrega en camino.');
    }

    private function repartidorActual(Request $request): Repartidor
    {
        $repartidor = $request->user()->repartidor;

        abort_if($repartidor === null, 403, 'Tu usuario no tiene una ficha de repartidor asociada.');

        return $repartidor;
    }
}

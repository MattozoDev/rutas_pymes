<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EntregaController extends Controller
{
    public function index(Request $request): void
    {
        abort_if($request->user()->repartidor === null, 403, 'Tu usuario no tiene una ficha de repartidor asociada.');
    }

    public function show(Entrega $entrega): void
    {
        Gate::authorize('view', $entrega);
    }

    public function update(Entrega $entrega): void
    {
        Gate::authorize('update', $entrega);

        if ($entrega->estado === EstadoEntrega::Asignada) {
            $entrega->update(['estado' => EstadoEntrega::EnCamino]);
        }
    }
}

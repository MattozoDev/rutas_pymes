<?php

namespace App\Http\Controllers;

use App\Models\Entrega;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class EvidenciaEntregaController extends Controller
{
    public function __invoke(Entrega $entrega): void
    {
        Gate::authorize('view', $entrega);

        abort_if($entrega->evidencia_path === null || ! Storage::exists($entrega->evidencia_path), 404);
    }
}

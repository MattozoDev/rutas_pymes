<?php

namespace App\Http\Controllers;

use App\Enums\ResultadoEntrega;
use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComprobanteController extends Controller
{
    public function index(Request $request): void
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'resultado' => ['nullable', Rule::enum(ResultadoEntrega::class)],
        ]);
    }

    public function show(Comprobante $comprobante): void {}
}

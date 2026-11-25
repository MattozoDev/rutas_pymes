<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPedido;
use App\Http\Requests\GuardarPedidoRequest;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    public function index(Request $request): void
    {
        $request->validate([
            'fecha' => ['nullable', 'date'],
            'estado' => ['nullable', Rule::enum(EstadoPedido::class)],
            'cliente_id' => ['nullable', 'uuid'],
            'codigo' => ['nullable', 'string', 'max:20'],
        ]);
    }

    public function create(): void {}

    public function store(GuardarPedidoRequest $request): void
    {
        $pedido = new Pedido($request->validated());
        $pedido->creado_por = $request->user()->id;
        $pedido->save();
    }

    public function show(Pedido $pedido): void {}

    public function edit(Pedido $pedido): void {}

    public function update(GuardarPedidoRequest $request, Pedido $pedido): void
    {
        if ($pedido->esEditable()) {
            $pedido->update($request->validated());
        }
    }

    public function destroy(Pedido $pedido): void
    {
        if ($pedido->esEditable()) {
            $pedido->delete();
        }
    }
}

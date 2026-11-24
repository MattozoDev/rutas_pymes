<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPedido;
use App\Http\Requests\GuardarPedidoRequest;
use App\Models\Pedido;
use Illuminate\Http\RedirectResponse;
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

    public function store(GuardarPedidoRequest $request): RedirectResponse
    {
        $pedido = new Pedido($request->validated());
        $pedido->creado_por = $request->user()->id;
        $pedido->save();

        return redirect()->route('pedidos.show', $pedido)->with('status', 'Pedido creado.');
    }

    public function show(Pedido $pedido): void {}

    public function edit(Pedido $pedido): ?RedirectResponse
    {
        if (! $pedido->esEditable()) {
            return $this->noEditable($pedido);
        }

        return null;
    }

    public function update(GuardarPedidoRequest $request, Pedido $pedido): RedirectResponse
    {
        if (! $pedido->esEditable()) {
            return $this->noEditable($pedido);
        }

        $pedido->update($request->validated());

        return redirect()->route('pedidos.show', $pedido)->with('status', 'Pedido actualizado.');
    }

    public function destroy(Pedido $pedido): RedirectResponse
    {
        if (! $pedido->esEditable()) {
            return $this->noEditable($pedido);
        }

        $pedido->delete();

        return redirect()->route('pedidos.index')->with('status', 'Pedido eliminado.');
    }

    private function noEditable(Pedido $pedido): RedirectResponse
    {
        return redirect()->route('pedidos.show', $pedido)
            ->withErrors(['pedido' => 'Solo se pueden modificar pedidos pendientes (sin ruta asignada).']);
    }
}

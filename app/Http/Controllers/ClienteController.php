<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request): void {}

    public function create(): void {}

    public function store(GuardarClienteRequest $request): RedirectResponse
    {
        $cliente = Cliente::create($request->validated());

        return redirect()->route('clientes.show', $cliente)->with('status', 'Cliente creado.');
    }

    public function show(Cliente $cliente): void {}

    public function edit(Cliente $cliente): void {}

    public function update(GuardarClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.show', $cliente)->with('status', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        if ($cliente->pedidos()->exists()) {
            $cliente->update(['activo' => false]);

            return redirect()->route('clientes.index')->with('status', 'El cliente tiene pedidos: se desactivó.');
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('status', 'Cliente eliminado.');
    }
}

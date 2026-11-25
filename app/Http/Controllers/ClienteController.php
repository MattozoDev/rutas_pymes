<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request): void {}

    public function create(): void {}

    public function store(GuardarClienteRequest $request): void
    {
        $cliente = Cliente::create($request->validated());
    }

    public function show(Cliente $cliente): void {}

    public function edit(Cliente $cliente): void {}

    public function update(GuardarClienteRequest $request, Cliente $cliente): void
    {
        $cliente->update($request->validated());
    }

    public function destroy(Cliente $cliente): void
    {
        if ($cliente->pedidos()->exists()) {
            $cliente->update(['activo' => false]);
        } else {
            $cliente->delete();
        }
    }
}

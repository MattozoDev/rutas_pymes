<?php

namespace App\Http\Controllers;

use App\Enums\EstadoRuta;
use App\Http\Requests\GuardarRutaRequest;
use App\Models\Entrega;
use App\Models\IaSugerenciaRuta;
use App\Models\Ruta;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RutaController extends Controller
{
    public function index(Request $request): void
    {
        $request->validate([
            'fecha' => ['nullable', 'date'],
            'estado' => ['nullable', Rule::enum(EstadoRuta::class)],
            'repartidor_id' => ['nullable', 'uuid'],
        ]);
    }

    public function create(Request $request): void {}

    public function store(GuardarRutaRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        try {
            $ruta = DB::transaction(function () use ($datos, $request): Ruta {
                $ruta = new Ruta(Arr::except($datos, ['pedidos']));
                $ruta->creado_por = $request->user()->id;
                $ruta->save();

                $this->sincronizarEntregas($ruta, $datos['pedidos']);

                if (! empty($datos['ia_sugerencia_id'])) {
                    IaSugerenciaRuta::query()->whereKey($datos['ia_sugerencia_id'])->update(['aceptada' => true]);
                }

                return $ruta;
            });
        } catch (QueryException $excepcion) {
            return $this->errorDeCapacidad($excepcion);
        }

        return redirect()->route('rutas.show', $ruta)->with('status', 'Ruta planificada.');
    }

    public function show(Ruta $ruta): void
    {
        Gate::authorize('view', $ruta);
    }

    public function edit(Ruta $ruta): void
    {
        Gate::authorize('update', $ruta);
    }

    public function update(GuardarRutaRequest $request, Ruta $ruta): RedirectResponse
    {
        Gate::authorize('update', $ruta);

        $datos = $request->validated();

        try {
            DB::transaction(function () use ($ruta, $datos): void {
                $ruta->update(Arr::except($datos, ['pedidos']));
                $this->sincronizarEntregas($ruta, $datos['pedidos']);
            });
        } catch (QueryException $excepcion) {
            return $this->errorDeCapacidad($excepcion);
        }

        return redirect()->route('rutas.show', $ruta)->with('status', 'Ruta actualizada.');
    }

    public function destroy(Ruta $ruta): RedirectResponse
    {
        Gate::authorize('delete', $ruta);

        DB::transaction(function () use ($ruta): void {
            $ruta->entregas()->delete();
            $ruta->delete();
        });

        return redirect()->route('rutas.index')->with('status', 'Ruta eliminada.');
    }

    /**
     * @param  list<string>  $pedidoIds
     */
    private function sincronizarEntregas(Ruta $ruta, array $pedidoIds): void
    {
        $ruta->entregas()->whereNotIn('pedido_id', $pedidoIds)->delete();

        $ruta->entregas()->update(['orden_parada' => null]);

        foreach (array_values($pedidoIds) as $indice => $pedidoId) {
            Entrega::query()->updateOrCreate(
                ['pedido_id' => $pedidoId],
                [
                    'ruta_id' => $ruta->id,
                    'repartidor_id' => $ruta->repartidor_id,
                    'vehiculo_id' => $ruta->vehiculo_id,
                    'orden_parada' => $indice + 1,
                ],
            );
        }
    }

    private function errorDeCapacidad(QueryException $excepcion): RedirectResponse
    {
        if (! str_contains($excepcion->getMessage(), 'Capacidad insuficiente')) {
            throw $excepcion;
        }

        return back()->withInput()->withErrors([
            'pedidos' => 'El peso total de los pedidos supera la capacidad del vehículo seleccionado.',
        ]);
    }
}

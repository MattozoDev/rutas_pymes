<?php

namespace App\Services;

use App\Models\Entrega;
use App\Models\Pedido;
use App\Models\Ruta;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class AsignadorRuta
{
    /**
     * @param  array<string, mixed>  $datosRuta
     * @param  list<string>  $pedidoIds
     *
     * @throws CapacidadExcedidaException
     */
    public function guardar(?Ruta $ruta, array $datosRuta, array $pedidoIds, bool $optimizarOrden = true): Ruta
    {
        $vehiculo = Vehiculo::query()->findOrFail($datosRuta['vehiculo_id']);
        $pedidos = Pedido::query()->whereKey($pedidoIds)->get()->keyBy('id');

        $evaluacion = CapacidadVehiculo::evaluar($vehiculo->capacidad_kg, $pedidos->pluck('peso_kg'));

        if ($evaluacion->excede()) {
            throw CapacidadExcedidaException::desde($evaluacion);
        }

        $planificacion = $this->planificar($pedidos, $pedidoIds, $optimizarOrden);
        $porCalles = $this->recorridoPorCalles($pedidos, $planificacion['orden'], $vehiculo->tipo);
        $ingresadoPorCalles = $porCalles !== null && $planificacion['orden'] !== array_values($pedidoIds)
            ? $this->recorridoPorCalles($pedidos, array_values($pedidoIds), $vehiculo->tipo)
            : $porCalles;

        if ($porCalles !== null) {
            $datosRuta['distancia_km'] = $porCalles['distancia_km'];
            $datosRuta['duracion_min'] = $porCalles['duracion_min'] + count($pedidoIds) * PlanificadorRuta::MINUTOS_POR_PARADA;
        } else {
            $datosRuta['distancia_km'] = $planificacion['distancia_km'];
            $datosRuta['duracion_min'] = $planificacion['distancia_km'] === null
                ? null
                : PlanificadorRuta::estimarDuracionMin($planificacion['distancia_km'], count($pedidoIds), $vehiculo->tipo);
        }

        $datosRuta['trayecto'] = [
            'orden' => $planificacion['orden'],
            'calculo' => $porCalles !== null ? 'calles' : 'linea_recta',
            'distancia_orden_ingresado_km' => $porCalles !== null
                ? $ingresadoPorCalles['distancia_km'] ?? null
                : $planificacion['distancia_orden_ingresado_km'],
            'distancia_linea_recta_km' => $planificacion['distancia_km'],
            'metodo' => $optimizarOrden ? 'vecino_mas_cercano' : 'orden_manual',
        ];

        try {
            return DB::transaction(function () use ($ruta, $datosRuta, $planificacion): Ruta {
                $ruta ??= new Ruta;
                $ruta->fill($datosRuta);

                if (! $ruta->exists) {
                    $ruta->creado_por = auth()->id();
                }

                $ruta->save();

                $this->sincronizarEntregas($ruta, $planificacion['orden']);

                return $ruta;
            });
        } catch (QueryException $excepcion) {
            if (str_contains($excepcion->getMessage(), 'Capacidad insuficiente')) {
                throw CapacidadExcedidaException::desde($evaluacion);
            }

            throw $excepcion;
        }
    }

    /**
     * @param  Collection<string, Pedido>  $pedidos
     * @param  list<string>  $pedidoIds
     * @return array{orden: list<string>, distancia_km: float|null, distancia_orden_ingresado_km: float|null}
     */
    public function planificar(Collection $pedidos, array $pedidoIds, bool $optimizarOrden): array
    {
        $empresa = auth()->user()?->empresa;
        $origen = $empresa?->latitud !== null && $empresa?->longitud !== null
            ? ['lat' => (float) $empresa->latitud, 'lng' => (float) $empresa->longitud]
            : null;

        $paradas = [];

        foreach ($pedidoIds as $pedidoId) {
            $pedido = $pedidos[$pedidoId];
            $paradas[$pedidoId] = [
                'lat' => $pedido->latitud !== null ? (float) $pedido->latitud : null,
                'lng' => $pedido->longitud !== null ? (float) $pedido->longitud : null,
            ];
        }

        $todasConCoordenadas = collect($paradas)->every(fn (array $parada): bool => $parada['lat'] !== null && $parada['lng'] !== null);
        $distanciaOrdenIngresado = $todasConCoordenadas
            ? PlanificadorRuta::distanciaRecorrido($origen, array_values($pedidoIds), $paradas)
            : null;

        if (! $optimizarOrden) {
            return [
                'orden' => array_values($pedidoIds),
                'distancia_km' => $distanciaOrdenIngresado,
                'distancia_orden_ingresado_km' => $distanciaOrdenIngresado,
            ];
        }

        $resultado = PlanificadorRuta::ordenarPorVecinoMasCercano($origen, $paradas);

        return [
            'orden' => $resultado['orden'],
            'distancia_km' => $resultado['distancia_km'],
            'distancia_orden_ingresado_km' => $distanciaOrdenIngresado,
        ];
    }

    /**
     * @param  Collection<string, Pedido>  $pedidos
     * @param  list<string>  $orden
     * @return array{distancia_km: float, duracion_min: int}|null
     */
    private function recorridoPorCalles(Collection $pedidos, array $orden, ?string $tipoVehiculo): ?array
    {
        $empresa = auth()->user()?->empresa;
        $puntos = [];

        if ($empresa?->latitud !== null && $empresa?->longitud !== null) {
            $puntos[] = ['lat' => (float) $empresa->latitud, 'lng' => (float) $empresa->longitud];
        }

        foreach ($orden as $pedidoId) {
            $pedido = $pedidos[$pedidoId];

            if ($pedido->latitud === null || $pedido->longitud === null) {
                return null;
            }

            $puntos[] = ['lat' => (float) $pedido->latitud, 'lng' => (float) $pedido->longitud];
        }

        return RuteoCalles::calcular($puntos, $tipoVehiculo);
    }

    /**
     * @param  list<string>  $pedidoIds
     */
    private function sincronizarEntregas(Ruta $ruta, array $pedidoIds): void
    {
        $ruta->entregas()->whereNotIn('pedido_id', $pedidoIds)->delete();

        $ruta->entregas()->update(['orden_parada' => null]);

        foreach ($pedidoIds as $indice => $pedidoId) {
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
}

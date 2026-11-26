<?php

namespace App\Services;

use App\Enums\EstadoEntrega;
use App\Enums\EstadoPedido;
use App\Models\Entrega;
use App\Models\Pedido;
use Illuminate\Support\Collection;

final class MapaEntregas
{
    private const COLOR_PENDIENTE = '#2563eb';

    private const COLOR_EN_CAMINO = '#7c3aed';

    private const COLOR_ENTREGADO = '#16a34a';

    private const COLOR_FALLIDO = '#dc2626';

    /**
     * @return array{origen: array{lat: float, lng: float, titulo: string}|null, paradas: list<array{lat: float, lng: float, orden: string, titulo: string, detalle: string, color: string}>, sinCoordenadas: int, linea: bool, perfil: string}
     */
    public static function paraPedido(Pedido $pedido, ?string $tipoVehiculo = null): array
    {
        $color = match ($pedido->estado) {
            EstadoPedido::Entregado => self::COLOR_ENTREGADO,
            EstadoPedido::NoEntregado, EstadoPedido::Cancelado => self::COLOR_FALLIDO,
            EstadoPedido::EnReparto => self::COLOR_EN_CAMINO,
            default => self::COLOR_PENDIENTE,
        };

        return self::construir([[$pedido, '1', $color, $pedido->estado->getLabel()]], $tipoVehiculo);
    }

    /**
     * @param  Collection<int, Pedido>  $pedidos
     * @return array{origen: array{lat: float, lng: float, titulo: string}|null, paradas: list<array{lat: float, lng: float, orden: string, titulo: string, detalle: string, color: string}>, sinCoordenadas: int, linea: bool, perfil: string}
     */
    public static function paraPedidosEnOrden(Collection $pedidos, ?string $tipoVehiculo = null): array
    {
        return self::construir($pedidos->values()->map(
            fn (Pedido $pedido, int $indice): array => [$pedido, (string) ($indice + 1), self::COLOR_PENDIENTE, 'Parada sugerida'],
        )->all(), $tipoVehiculo);
    }

    /**
     * @param  Collection<int, Entrega>  $entregas
     * @return array{origen: array{lat: float, lng: float, titulo: string}|null, paradas: list<array{lat: float, lng: float, orden: string, titulo: string, detalle: string, color: string}>, sinCoordenadas: int, linea: bool, perfil: string}
     */
    public static function paraEntregas(Collection $entregas, ?string $tipoVehiculo = null): array
    {
        return self::construir($entregas->sortBy('orden_parada')->values()->map(fn (Entrega $entrega): array => [
            $entrega->pedido,
            (string) ($entrega->orden_parada ?? '•'),
            match ($entrega->estado) {
                EstadoEntrega::Entregada => self::COLOR_ENTREGADO,
                EstadoEntrega::NoEntregada => self::COLOR_FALLIDO,
                EstadoEntrega::EnCamino => self::COLOR_EN_CAMINO,
                default => self::COLOR_PENDIENTE,
            },
            $entrega->estado->getLabel(),
        ])->all(), $tipoVehiculo);
    }

    /**
     * @param  list<array{0: Pedido, 1: string, 2: string, 3: string}>  $puntos
     * @return array{origen: array{lat: float, lng: float, titulo: string}|null, paradas: list<array{lat: float, lng: float, orden: string, titulo: string, detalle: string, color: string}>, sinCoordenadas: int, linea: bool, perfil: string}
     */
    private static function construir(array $puntos, ?string $tipoVehiculo): array
    {
        $paradas = [];
        $sinCoordenadas = 0;

        foreach ($puntos as [$pedido, $orden, $color, $estado]) {
            if ($pedido->latitud === null || $pedido->longitud === null) {
                $sinCoordenadas++;

                continue;
            }

            $paradas[] = [
                'lat' => (float) $pedido->latitud,
                'lng' => (float) $pedido->longitud,
                'orden' => $orden,
                'titulo' => trim($pedido->codigo.' · '.$pedido->cliente?->nombre, ' ·'),
                'detalle' => trim($pedido->direccion_entrega.($pedido->comuna ? ', '.$pedido->comuna : ''))
                    .' · '.$pedido->resumenCarga().' · '.$estado,
                'color' => $color,
            ];
        }

        return [
            'origen' => self::origen(),
            'paradas' => $paradas,
            'sinCoordenadas' => $sinCoordenadas,
            'linea' => true,
            'perfil' => $tipoVehiculo === 'bicicleta' ? 'bike' : 'car',
        ];
    }

    /**
     * @return array{lat: float, lng: float, titulo: string}|null
     */
    private static function origen(): ?array
    {
        $empresa = auth()->user()?->empresa;

        if ($empresa?->latitud === null || $empresa->longitud === null) {
            return null;
        }

        return [
            'lat' => (float) $empresa->latitud,
            'lng' => (float) $empresa->longitud,
            'titulo' => $empresa->nombre.' · punto de salida',
        ];
    }
}

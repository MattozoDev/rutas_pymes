<?php

namespace App\Services;

final readonly class ResultadoIndicadores
{
    public function __construct(
        public ?float $minutosPorPedidoManual,
        public ?float $minutosPorPedidoSistema,
        public ?float $kmMotorizadosPorPedidoManual,
        public ?float $kmMotorizadosPorPedidoSistema,
        public ?float $reduccionTiempoPorcentaje,
        public ?float $reduccionKmPorcentaje,
        public int $mediciones,
    ) {}

    public function cumpleMetaTiempo(): ?bool
    {
        return $this->reduccionTiempoPorcentaje === null
            ? null
            : $this->reduccionTiempoPorcentaje >= IndicadoresPlanificacion::META_REDUCCION_TIEMPO;
    }

    public function cumpleMetaKm(): ?bool
    {
        return $this->reduccionKmPorcentaje === null
            ? null
            : $this->reduccionKmPorcentaje >= IndicadoresPlanificacion::META_REDUCCION_KM;
    }
}

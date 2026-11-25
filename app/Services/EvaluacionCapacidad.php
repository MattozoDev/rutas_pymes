<?php

namespace App\Services;

final readonly class EvaluacionCapacidad
{
    public function __construct(
        public float $cargaKg,
        public float $capacidadKg,
    ) {}

    public function excede(): bool
    {
        return round($this->cargaKg, 2) > round($this->capacidadKg, 2);
    }

    public function porcentaje(): int
    {
        if ($this->capacidadKg <= 0) {
            return $this->cargaKg > 0 ? 100 : 0;
        }

        return (int) round($this->cargaKg / $this->capacidadKg * 100);
    }

    public function disponibleKg(): float
    {
        return max(0.0, round($this->capacidadKg - $this->cargaKg, 2));
    }

    public function mensaje(): string
    {
        $titulo = $this->excede() ? 'Capacidad excedida' : 'Capacidad suficiente';

        return sprintf(
            '%s · %s / %s kg (%d %%)',
            $titulo,
            CapacidadVehiculo::formatearKg($this->cargaKg),
            CapacidadVehiculo::formatearKg($this->capacidadKg),
            $this->porcentaje(),
        );
    }
}

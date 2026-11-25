<?php

namespace App\Services;

use RuntimeException;

class CapacidadExcedidaException extends RuntimeException
{
    public static function desde(EvaluacionCapacidad $evaluacion): self
    {
        return new self(sprintf(
            'El peso total de la ruta (%s kg) supera la capacidad del vehículo (%s kg). Quita pedidos o elige un vehículo más grande.',
            CapacidadVehiculo::formatearKg($evaluacion->cargaKg),
            CapacidadVehiculo::formatearKg($evaluacion->capacidadKg),
        ));
    }
}

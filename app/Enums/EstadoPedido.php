<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoPedido: string implements HasColor, HasLabel
{
    case Pendiente = 'pendiente';
    case Asignado = 'asignado';
    case EnReparto = 'en_reparto';
    case Entregado = 'entregado';
    case NoEntregado = 'no_entregado';
    case Cancelado = 'cancelado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Asignado => 'Asignado',
            self::EnReparto => 'En reparto',
            self::Entregado => 'Entregado',
            self::NoEntregado => 'No entregado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Asignado => 'info',
            self::EnReparto => 'primary',
            self::Entregado => 'success',
            self::NoEntregado => 'danger',
            self::Cancelado => 'gray',
        };
    }
}

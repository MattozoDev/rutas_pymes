<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ResultadoEntrega: string implements HasColor, HasLabel
{
    case Entregado = 'entregado';
    case NoEntregado = 'no_entregado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Entregado => 'Entregado',
            self::NoEntregado => 'No entregado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Entregado => 'success',
            self::NoEntregado => 'danger',
        };
    }
}

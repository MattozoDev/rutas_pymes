<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoRepartidor: string implements HasColor, HasLabel
{
    case Disponible = 'disponible';
    case EnRuta = 'en_ruta';
    case Inactivo = 'inactivo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::EnRuta => 'En ruta',
            self::Inactivo => 'Inactivo',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Disponible => 'success',
            self::EnRuta => 'info',
            self::Inactivo => 'gray',
        };
    }
}

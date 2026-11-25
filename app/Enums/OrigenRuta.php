<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrigenRuta: string implements HasLabel
{
    case Manual = 'manual';
    case Ia = 'ia';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Ia => 'Sugerida',
        };
    }
}

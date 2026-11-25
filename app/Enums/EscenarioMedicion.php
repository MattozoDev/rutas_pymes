<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EscenarioMedicion: string implements HasColor, HasLabel
{
    case Manual = 'manual';
    case Sistema = 'sistema';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual (antes)',
            self::Sistema => 'Con rutapyme',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Manual => 'gray',
            self::Sistema => 'primary',
        };
    }
}

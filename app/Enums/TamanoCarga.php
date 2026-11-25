<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TamanoCarga: string implements HasLabel
{
    case Pequeno = 'pequeno';
    case Mediano = 'mediano';
    case Grande = 'grande';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pequeno => 'Pequeño',
            self::Mediano => 'Mediano',
            self::Grande => 'Grande',
        };
    }
}

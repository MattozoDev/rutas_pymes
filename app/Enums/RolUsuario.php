<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RolUsuario: string implements HasLabel
{
    case Administrador = 'administrador';
    case Repartidor = 'repartidor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Repartidor => 'Repartidor',
        };
    }
}

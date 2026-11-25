<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoEntrega: string implements HasColor, HasLabel
{
    case Asignada = 'asignada';
    case EnCamino = 'en_camino';
    case Entregada = 'entregada';
    case NoEntregada = 'no_entregada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Asignada => 'Asignada',
            self::EnCamino => 'En camino',
            self::Entregada => 'Entregada',
            self::NoEntregada => 'No entregada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Asignada => 'info',
            self::EnCamino => 'primary',
            self::Entregada => 'success',
            self::NoEntregada => 'danger',
        };
    }
}

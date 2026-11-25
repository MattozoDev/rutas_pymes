<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoRuta: string implements HasColor, HasLabel
{
    case Borrador = 'borrador';
    case Planificada = 'planificada';
    case EnCurso = 'en_curso';
    case Completada = 'completada';
    case Cancelada = 'cancelada';

    public function puedeCambiarA(self $nuevoEstado): bool
    {
        return match ($this) {
            self::Borrador, self::Planificada => in_array($nuevoEstado, [self::EnCurso, self::Cancelada], true),
            self::EnCurso => in_array($nuevoEstado, [self::Completada, self::Cancelada], true),
            self::Completada, self::Cancelada => false,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Planificada => 'Planificada',
            self::EnCurso => 'En curso',
            self::Completada => 'Completada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::Planificada => 'info',
            self::EnCurso => 'primary',
            self::Completada => 'success',
            self::Cancelada => 'danger',
        };
    }
}

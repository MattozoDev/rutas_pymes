<?php

namespace App\Enums;

enum EstadoRuta: string
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
}

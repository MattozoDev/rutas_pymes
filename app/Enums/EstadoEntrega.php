<?php

namespace App\Enums;

enum EstadoEntrega: string
{
    case Asignada = 'asignada';
    case EnCamino = 'en_camino';
    case Entregada = 'entregada';
    case NoEntregada = 'no_entregada';
}

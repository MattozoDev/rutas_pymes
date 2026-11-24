<?php

namespace App\Enums;

enum EstadoPedido: string
{
    case Pendiente = 'pendiente';
    case Asignado = 'asignado';
    case EnReparto = 'en_reparto';
    case Entregado = 'entregado';
    case NoEntregado = 'no_entregado';
    case Cancelado = 'cancelado';
}

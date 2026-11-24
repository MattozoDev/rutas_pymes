<?php

namespace App\Enums;

enum EstadoRepartidor: string
{
    case Disponible = 'disponible';
    case EnRuta = 'en_ruta';
    case Inactivo = 'inactivo';
}

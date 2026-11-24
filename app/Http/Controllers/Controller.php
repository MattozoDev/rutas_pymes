<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function hoy(): string
    {
        return now(config('app.zona_horaria_negocio'))->toDateString();
    }
}

<?php

namespace App\Filament\Concerns;

trait SoloAdministradores
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->esAdministrador() && parent::canAccess();
    }
}

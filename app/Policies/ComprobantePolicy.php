<?php

namespace App\Policies;

use App\Models\Comprobante;
use App\Models\User;

class ComprobantePolicy
{
    public function view(User $user, Comprobante $comprobante): bool
    {
        if ($user->empresa_id !== $comprobante->empresa_id) {
            return false;
        }

        return $user->esAdministrador()
            || ($user->esRepartidor() && $user->repartidor?->id === $comprobante->repartidor_id);
    }
}

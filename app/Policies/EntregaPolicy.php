<?php

namespace App\Policies;

use App\Models\Entrega;
use App\Models\User;

class EntregaPolicy
{
    public function view(User $user, Entrega $entrega): bool
    {
        return $user->esAdministrador() || $this->esDelRepartidor($user, $entrega);
    }

    public function update(User $user, Entrega $entrega): bool
    {
        return $this->esDelRepartidor($user, $entrega) && ! $entrega->estaFinalizada();
    }

    private function esDelRepartidor(User $user, Entrega $entrega): bool
    {
        return $user->esRepartidor()
            && $user->repartidor !== null
            && $user->repartidor->id === $entrega->repartidor_id;
    }
}

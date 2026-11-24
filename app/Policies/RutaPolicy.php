<?php

namespace App\Policies;

use App\Models\Ruta;
use App\Models\User;

class RutaPolicy
{
    public function view(User $user, Ruta $ruta): bool
    {
        return $user->esAdministrador() || $this->esDelRepartidor($user, $ruta);
    }

    public function cambiarEstado(User $user, Ruta $ruta): bool
    {
        return $user->esAdministrador() || $this->esDelRepartidor($user, $ruta);
    }

    public function update(User $user, Ruta $ruta): bool
    {
        return $user->esAdministrador() && $ruta->esModificable();
    }

    public function delete(User $user, Ruta $ruta): bool
    {
        return $user->esAdministrador() && $ruta->esModificable();
    }

    private function esDelRepartidor(User $user, Ruta $ruta): bool
    {
        return $user->esRepartidor()
            && $user->repartidor !== null
            && $user->repartidor->id === $ruta->repartidor_id;
    }
}

<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $user, User $usuario): bool
    {
        return $user->esAdministrador() && $user->empresa_id === $usuario->empresa_id;
    }

    public function delete(User $user, User $usuario): bool
    {
        return $this->update($user, $usuario) && $user->id !== $usuario->id;
    }
}

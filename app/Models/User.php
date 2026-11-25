<?php

namespace App\Models;

use App\Enums\RolUsuario;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'empresa_id', 'rol', 'telefono', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'rol' => 'administrador',
        'activo' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => RolUsuario::class,
            'activo' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->activo && $this->empresa_id !== null;
    }

    public function esAdministrador(): bool
    {
        return $this->rol === RolUsuario::Administrador;
    }

    public function esRepartidor(): bool
    {
        return $this->rol === RolUsuario::Repartidor;
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * @return HasOne<Repartidor, $this>
     */
    public function repartidor(): HasOne
    {
        return $this->hasOne(Repartidor::class);
    }

    /**
     * @return HasMany<Notificacion, $this>
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }
}

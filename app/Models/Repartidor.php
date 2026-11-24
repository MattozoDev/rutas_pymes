<?php

namespace App\Models;

use App\Enums\EstadoRepartidor;
use App\Models\Concerns\PerteneceAEmpresa;
use Database\Factories\RepartidorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('repartidores')]
#[Fillable(['user_id', 'nombre', 'telefono', 'vehiculo_id', 'estado'])]
class Repartidor extends Model
{
    /** @use HasFactory<RepartidorFactory> */
    use HasFactory, HasUuids, PerteneceAEmpresa;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'disponible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoRepartidor::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Vehiculo, $this>
     */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /**
     * @return HasMany<Ruta, $this>
     */
    public function rutas(): HasMany
    {
        return $this->hasMany(Ruta::class);
    }

    /**
     * @return HasMany<Entrega, $this>
     */
    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static Builder<static> noLeidas()
 */
#[Table('notificaciones')]
#[Fillable(['user_id', 'titulo', 'mensaje', 'enlace', 'leida'])]
class Notificacion extends Model
{
    use HasUuids, PerteneceAEmpresa;

    public const UPDATED_AT = null;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'leida' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'leida' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Notificacion>  $query
     */
    public function scopeNoLeidas(Builder $query): void
    {
        $query->where('leida', false);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

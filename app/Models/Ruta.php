<?php

namespace App\Models;

use App\Enums\EstadoEntrega;
use App\Enums\EstadoRuta;
use App\Enums\OrigenRuta;
use App\Models\Concerns\PerteneceAEmpresa;
use Database\Factories\RutaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Table('rutas')]
#[Fillable([
    'repartidor_id', 'vehiculo_id', 'fecha', 'origen', 'ia_sugerencia_id',
    'distancia_km', 'duracion_min', 'trayecto', 'observaciones',
])]
class Ruta extends Model
{
    /** @use HasFactory<RutaFactory> */
    use HasFactory, HasUuids, PerteneceAEmpresa;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'planificada',
        'origen' => 'manual',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoRuta::class,
            'origen' => OrigenRuta::class,
            'distancia_km' => 'decimal:2',
            'duracion_min' => 'integer',
            'trayecto' => 'array',
            'iniciada_at' => 'datetime',
            'finalizada_at' => 'datetime',
        ];
    }

    public function esModificable(): bool
    {
        return in_array($this->estado, [EstadoRuta::Borrador, EstadoRuta::Planificada], true);
    }

    public function cambiarEstado(EstadoRuta $nuevoEstado): bool
    {
        $entregasAbiertas = $this->entregas()->getQuery()
            ->whereIn('estado', [EstadoEntrega::Asignada, EstadoEntrega::EnCamino]);

        if (! $this->estado->puedeCambiarA($nuevoEstado)
            || ($nuevoEstado === EstadoRuta::Completada && $entregasAbiertas->exists())) {
            return false;
        }

        DB::transaction(function () use ($nuevoEstado, $entregasAbiertas): void {
            if ($nuevoEstado === EstadoRuta::Cancelada) {
                $entregasAbiertas->delete();
            }

            $this->estado = $nuevoEstado;
            $this->save();
        });

        return true;
    }

    /**
     * @return BelongsTo<Repartidor, $this>
     */
    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(Repartidor::class);
    }

    /**
     * @return BelongsTo<Vehiculo, $this>
     */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /**
     * @return HasMany<Entrega, $this>
     */
    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class)->orderBy('orden_parada');
    }

    /**
     * @return BelongsTo<IaSugerenciaRuta, $this>
     */
    public function iaSugerencia(): BelongsTo
    {
        return $this->belongsTo(IaSugerenciaRuta::class, 'ia_sugerencia_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}

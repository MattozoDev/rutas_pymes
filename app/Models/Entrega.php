<?php

namespace App\Models;

use App\Enums\EstadoEntrega;
use App\Models\Concerns\PerteneceAEmpresa;
use Database\Factories\EntregaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('entregas')]
#[Fillable([
    'pedido_id', 'ruta_id', 'repartidor_id', 'vehiculo_id', 'orden_parada', 'estado',
    'observaciones_repartidor', 'receptor_nombre', 'motivo_no_entrega', 'evidencia_path',
    'latitud_confirmacion', 'longitud_confirmacion',
])]
class Entrega extends Model
{
    /** @use HasFactory<EntregaFactory> */
    use HasFactory, HasUuids, PerteneceAEmpresa;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'asignada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoEntrega::class,
            'orden_parada' => 'integer',
            'latitud_confirmacion' => 'decimal:6',
            'longitud_confirmacion' => 'decimal:6',
            'finalizada_at' => 'datetime',
        ];
    }

    public function estaFinalizada(): bool
    {
        return in_array($this->estado, [EstadoEntrega::Entregada, EstadoEntrega::NoEntregada], true);
    }

    /**
     * @return BelongsTo<Pedido, $this>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * @return BelongsTo<Ruta, $this>
     */
    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class);
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
     * @return HasOne<Comprobante, $this>
     */
    public function comprobante(): HasOne
    {
        return $this->hasOne(Comprobante::class);
    }
}

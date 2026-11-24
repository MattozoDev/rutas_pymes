<?php

namespace App\Models;

use App\Enums\EstadoPedido;
use App\Enums\TamanoCarga;
use App\Models\Concerns\PerteneceAEmpresa;
use Database\Factories\PedidoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('pedidos')]
#[Fillable([
    'cliente_id', 'fecha_entrega', 'franja_inicio', 'franja_fin', 'direccion_entrega', 'comuna',
    'referencia', 'latitud', 'longitud', 'bultos', 'peso_kg', 'tamano', 'descripcion_carga', 'observaciones',
])]
class Pedido extends Model
{
    /** @use HasFactory<PedidoFactory> */
    use HasFactory, HasUuids, PerteneceAEmpresa;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'pendiente',
        'tamano' => 'mediano',
        'bultos' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_entrega' => 'date',
            'latitud' => 'decimal:6',
            'longitud' => 'decimal:6',
            'bultos' => 'integer',
            'peso_kg' => 'decimal:2',
            'numero' => 'integer',
            'tamano' => TamanoCarga::class,
            'estado' => EstadoPedido::class,
        ];
    }

    public function esEditable(): bool
    {
        return $this->estado === EstadoPedido::Pendiente;
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * @return HasOne<Entrega, $this>
     */
    public function entrega(): HasOne
    {
        return $this->hasOne(Entrega::class);
    }

    /**
     * @return HasOne<Comprobante, $this>
     */
    public function comprobante(): HasOne
    {
        return $this->hasOne(Comprobante::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}

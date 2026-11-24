<?php

namespace App\Models;

use App\Casts\ArregloUuidPostgres;
use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('ia_sugerencias_ruta')]
#[Fillable(['solicitado_por', 'vehiculo_id', 'pedidos_ids', 'proveedor', 'modelo', 'entrada', 'respuesta', 'aceptada', 'latencia_ms'])]
class IaSugerenciaRuta extends Model
{
    use HasUuids, PerteneceAEmpresa;

    public const UPDATED_AT = null;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'pedidos_ids' => ArregloUuidPostgres::class,
            'entrada' => 'array',
            'respuesta' => 'array',
            'aceptada' => 'boolean',
            'latencia_ms' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Vehiculo, $this>
     */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }
}

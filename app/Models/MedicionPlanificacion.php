<?php

namespace App\Models;

use App\Enums\EscenarioMedicion;
use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('mediciones_planificacion')]
#[Fillable([
    'fecha', 'escenario', 'grupo_comparacion', 'n_pedidos', 'tipo_vehiculo',
    'tiempo_planificacion_min', 'km_recorridos', 'km_motorizados', 'notas',
])]
class MedicionPlanificacion extends Model
{
    use HasUuids, PerteneceAEmpresa;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'escenario' => EscenarioMedicion::class,
            'n_pedidos' => 'integer',
            'tiempo_planificacion_min' => 'decimal:2',
            'km_recorridos' => 'decimal:2',
            'km_motorizados' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}

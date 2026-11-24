<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('tipos_vehiculo', key: 'codigo', keyType: 'string', incrementing: false, timestamps: false)]
class TipoVehiculo extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacidad_kg_defecto' => 'decimal:2',
            'bajas_emisiones' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /**
     * @return HasMany<Vehiculo, $this>
     */
    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class, 'tipo', 'codigo');
    }
}

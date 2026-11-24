<?php

namespace App\Http\Requests;

use App\Models\Vehiculo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Vehiculo|null $vehiculo */
        $vehiculo = $this->route('vehiculo');

        return [
            'tipo' => ['required', 'string', Rule::exists('tipos_vehiculo', 'codigo')],
            'identificador' => [
                'required', 'string', 'max:50',
                Rule::unique('vehiculos', 'identificador')
                    ->where('empresa_id', $this->user()->empresa_id)
                    ->ignore($vehiculo?->id),
            ],
            'capacidad_kg' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}

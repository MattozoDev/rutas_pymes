<?php

namespace App\Http\Requests;

use App\Enums\EscenarioMedicion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMedicionRequest extends FormRequest
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
        return [
            'fecha' => ['required', 'date'],
            'escenario' => ['required', Rule::enum(EscenarioMedicion::class)],
            'grupo_comparacion' => ['nullable', 'string', 'max:100'],
            'n_pedidos' => ['required', 'integer', 'min:1'],
            'tipo_vehiculo' => ['nullable', 'string', Rule::exists('tipos_vehiculo', 'codigo')],
            'tiempo_planificacion_min' => ['required', 'numeric', 'min:0'],
            'km_recorridos' => ['nullable', 'numeric', 'min:0'],
            'km_motorizados' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

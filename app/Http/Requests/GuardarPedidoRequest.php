<?php

namespace App\Http\Requests;

use App\Enums\TamanoCarga;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarPedidoRequest extends FormRequest
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
            'cliente_id' => [
                'required', 'uuid',
                Rule::exists('clientes', 'id')->where('empresa_id', $this->user()->empresa_id),
            ],
            'fecha_entrega' => ['required', 'date'],
            'franja_inicio' => ['nullable', 'date_format:H:i'],
            'franja_fin' => ['nullable', 'date_format:H:i', 'after:franja_inicio'],
            'direccion_entrega' => ['required', 'string', 'max:255'],
            'comuna' => ['nullable', 'string', 'max:100'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'bultos' => ['required', 'integer', 'min:1'],
            'peso_kg' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'tamano' => ['required', Rule::enum(TamanoCarga::class)],
            'descripcion_carga' => ['nullable', 'string', 'max:1000'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

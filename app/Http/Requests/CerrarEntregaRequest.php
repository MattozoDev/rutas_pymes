<?php

namespace App\Http\Requests;

use App\Enums\EstadoEntrega;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CerrarEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('entrega'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in([EstadoEntrega::Entregada->value, EstadoEntrega::NoEntregada->value])],
            'receptor_nombre' => ['required_if:estado,entregada', 'nullable', 'string', 'max:255'],
            'motivo_no_entrega' => ['required_if:estado,no_entregada', 'nullable', 'string', 'max:1000'],
            'observaciones_repartidor' => ['nullable', 'string', 'max:2000'],
            'evidencia' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'latitud_confirmacion' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud_confirmacion' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}

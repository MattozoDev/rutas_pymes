<?php

namespace App\Http\Requests;

use App\Enums\EstadoPedido;
use App\Enums\OrigenRuta;
use App\Models\Ruta;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarRutaRequest extends FormRequest
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
        $empresaId = $this->user()->empresa_id;

        /** @var Ruta|null $ruta */
        $ruta = $this->route('ruta');
        $pedidosDeLaRuta = $ruta?->entregas()->pluck('pedido_id')->all() ?? [];

        return [
            'repartidor_id' => ['required', 'uuid', Rule::exists('repartidores', 'id')->where('empresa_id', $empresaId)],
            'vehiculo_id' => [
                'required', 'uuid',
                Rule::exists('vehiculos', 'id')->where('empresa_id', $empresaId)->where('activo', true),
            ],
            'fecha' => ['required', 'date'],
            'origen' => ['sometimes', Rule::enum(OrigenRuta::class)],
            'ia_sugerencia_id' => [
                'nullable', 'uuid',
                Rule::exists('ia_sugerencias_ruta', 'id')->where('empresa_id', $empresaId),
            ],
            'distancia_km' => ['nullable', 'numeric', 'min:0'],
            'duracion_min' => ['nullable', 'integer', 'min:0'],
            'trayecto' => ['nullable', 'array'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'pedidos' => ['required', 'array', 'min:1'],
            'pedidos.*' => [
                'uuid', 'distinct',
                Rule::exists('pedidos', 'id')
                    ->where('empresa_id', $empresaId)
                    ->where(fn ($query) => $query
                        ->where('estado', EstadoPedido::Pendiente->value)
                        ->orWhereIn('id', $pedidosDeLaRuta)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pedidos.*.exists' => 'El pedido seleccionado no existe o ya no está pendiente.',
        ];
    }
}

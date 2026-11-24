<?php

namespace App\Http\Requests;

use App\Enums\EstadoRepartidor;
use App\Models\Repartidor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class GuardarRepartidorRequest extends FormRequest
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
        /** @var Repartidor|null $repartidor */
        $repartidor = $this->route('repartidor');
        $empresaId = $this->user()->empresa_id;

        return [
            'nombre' => ['required', 'string', 'min:2', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'vehiculo_id' => [
                'nullable', 'uuid',
                Rule::exists('vehiculos', 'id')->where('empresa_id', $empresaId)->where('activo', true),
            ],
            'estado' => ['sometimes', Rule::enum(EstadoRepartidor::class)],
            'email' => [
                Rule::requiredIf($repartidor === null && $this->filled('password')),
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($repartidor?->user_id),
            ],
            'password' => ['nullable', Password::defaults()],
        ];
    }
}

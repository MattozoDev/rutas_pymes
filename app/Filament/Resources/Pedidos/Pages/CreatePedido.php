<?php

namespace App\Filament\Resources\Pedidos\Pages;

use App\Filament\Resources\Pedidos\PedidoResource;
use App\Filament\Resources\Rutas\RutaResource;
use App\Filament\Schemas\UbicacionEnMapa;
use App\Models\Cliente;
use App\Models\Pedido;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePedido extends CreateRecord
{
    protected static string $resource = PedidoResource::class;

    protected static ?string $title = 'Crear pedido';

    public bool $asignarDespuesDeCrear = false;

    public function mount(): void
    {
        parent::mount();

        $cliente = filled($clienteId = request()->query('cliente'))
            ? Cliente::query()->find($clienteId)
            : null;

        if ($cliente !== null) {
            $this->data['cliente_id'] = $cliente->id;
            $this->data['direccion_entrega'] = $cliente->direccion;
            $this->data['comuna'] = $cliente->comuna;
            $this->data['latitud'] = $cliente->latitud;
            $this->data['longitud'] = $cliente->longitud;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $pedido = new Pedido(UbicacionEnMapa::completarCoordenadas($data, 'direccion_entrega', 'comuna'));
        $pedido->creado_por = auth()->id();
        $pedido->save();

        return $pedido;
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('crearYAsignar')
                ->label('Crear y asignar repartidor')
                ->action(function (): void {
                    $this->asignarDespuesDeCrear = true;
                    $this->create();
                }),
            $this->getCreateFormAction()->label('Crear')->color('gray'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->asignarDespuesDeCrear) {
            return RutaResource::getUrl('create', ['pedidos' => $this->getRecord()->getKey()]);
        }

        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}

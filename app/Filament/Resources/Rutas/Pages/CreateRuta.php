<?php

namespace App\Filament\Resources\Rutas\Pages;

use App\Filament\Resources\Rutas\RutaResource;
use App\Models\Pedido;
use App\Services\AsignadorRuta;
use App\Services\CapacidadExcedidaException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateRuta extends CreateRecord
{
    protected static string $resource = RutaResource::class;

    protected static ?string $title = 'Asignar entrega';

    public function mount(): void
    {
        parent::mount();

        $pedidoIds = array_values(array_filter(explode(',', (string) request()->query('pedidos'))));

        if ($pedidoIds === []) {
            return;
        }

        $pedidos = Pedido::query()->whereKey($pedidoIds)->get(['id', 'fecha_entrega']);
        $this->data['pedidos'] = $pedidos->pluck('id')->all();

        if ($pedidos->isNotEmpty()) {
            $this->data['fecha'] = $pedidos->max('fecha_entrega')->toDateString();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AsignadorRuta::class)->guardar(
                ruta: null,
                datosRuta: Arr::except($data, ['pedidos', 'optimizar']),
                pedidoIds: array_values($data['pedidos']),
                optimizarOrden: (bool) ($data['optimizar'] ?? true),
            );
        } catch (CapacidadExcedidaException $excepcion) {
            Notification::make()->danger()->title('Capacidad insuficiente')->body($excepcion->getMessage())->persistent()->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): string
    {
        return 'Asignación guardada. El repartidor ya ve sus paradas.';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Guardar asignación')->icon('heroicon-o-check'),
            $this->getCancelFormAction(),
        ];
    }
}

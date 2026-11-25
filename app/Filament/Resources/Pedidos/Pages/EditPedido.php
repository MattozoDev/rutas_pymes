<?php

namespace App\Filament\Resources\Pedidos\Pages;

use App\Filament\Resources\Pedidos\PedidoResource;
use App\Models\Pedido;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPedido extends EditRecord
{
    protected static string $resource = PedidoResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Pedido $pedido */
        $pedido = $this->getRecord();

        if (! $pedido->esEditable()) {
            Notification::make()
                ->warning()
                ->title('Solo se pueden modificar pedidos pendientes (sin ruta asignada).')
                ->send();

            $this->redirect(PedidoResource::getUrl('view', ['record' => $pedido]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}

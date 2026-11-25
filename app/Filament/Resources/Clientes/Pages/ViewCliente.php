<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use App\Filament\Resources\Pedidos\PedidoResource;
use App\Models\Cliente;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCliente extends ViewRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nuevoPedido')
                ->label('Nuevo pedido')
                ->icon(Heroicon::Plus)
                ->url(fn (Cliente $record): string => PedidoResource::getUrl('create', ['cliente' => $record->id])),
            EditAction::make(),
        ];
    }
}

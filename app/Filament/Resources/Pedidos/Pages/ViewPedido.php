<?php

namespace App\Filament\Resources\Pedidos\Pages;

use App\Filament\Resources\Comprobantes\ComprobanteResource;
use App\Filament\Resources\Pedidos\PedidoResource;
use App\Filament\Resources\Rutas\RutaResource;
use App\Models\Pedido;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPedido extends ViewRecord
{
    protected static string $resource = PedidoResource::class;

    public function getTitle(): string
    {
        /** @var Pedido $pedido */
        $pedido = $this->getRecord();

        return 'Pedido '.$pedido->codigo;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('asignar')
                ->label('Asignar')
                ->icon(Heroicon::OutlinedMap)
                ->visible(fn (Pedido $record): bool => $record->esEditable())
                ->url(fn (Pedido $record): string => RutaResource::getUrl('create', ['pedidos' => $record->id])),
            Action::make('verComprobante')
                ->label('Ver comprobante')
                ->icon(Heroicon::OutlinedDocumentCheck)
                ->color('gray')
                ->visible(fn (Pedido $record): bool => $record->comprobante !== null)
                ->url(fn (Pedido $record): string => ComprobanteResource::getUrl('view', ['record' => $record->comprobante])),
            EditAction::make()->visible(fn (Pedido $record): bool => $record->esEditable()),
        ];
    }
}

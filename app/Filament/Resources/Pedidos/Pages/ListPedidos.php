<?php

namespace App\Filament\Resources\Pedidos\Pages;

use App\Filament\Resources\Pedidos\PedidoResource;
use App\Filament\Resources\Pedidos\Tables\PedidosTable;
use App\Models\Pedido;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPedidos extends ListRecords
{
    protected static string $resource = PedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo pedido'),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $conteos = Pedido::resumenPorEstado()->map(fn (object $fila): int => (int) $fila->total);

        $pestanas = [];

        foreach (PedidosTable::estadosParaPestanas() as $clave => $estado) {
            $pestanas[$clave] = Tab::make($estado?->getLabel() ?? 'Todos')
                ->badge($estado === null ? $conteos->sum() : ($conteos[$estado->value] ?? 0))
                ->modifyQueryUsing(fn (Builder $query): Builder => $estado === null ? $query : $query->where('estado', $estado));
        }

        return $pestanas;
    }

    public function getDefaultActiveTab(): string
    {
        return 'todos';
    }
}

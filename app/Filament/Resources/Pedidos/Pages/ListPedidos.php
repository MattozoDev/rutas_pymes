<?php

namespace App\Filament\Resources\Pedidos\Pages;

use App\Filament\Resources\Pedidos\PedidoResource;
use App\Filament\Resources\Pedidos\Tables\PedidosTable;
use App\Models\Pedido;
use App\Services\ExportadorCsv;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListPedidos extends ListRecords
{
    protected static string $resource = PedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportar')
                ->label('Exportar')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->tooltip('Descarga en CSV (Excel) los pedidos con la pestaña, búsqueda y filtros actuales')
                ->action(fn (): StreamedResponse => $this->exportar()),
            CreateAction::make()->label('Nuevo pedido'),
        ];
    }

    private function exportar(): StreamedResponse
    {
        $pedidos = $this->getFilteredSortedTableQuery()
            ->with(['cliente', 'entrega.repartidor', 'comprobante'])
            ->lazy(500)
            ->map(fn (Pedido $pedido): array => [
                $pedido->codigo,
                $pedido->fecha_entrega?->format('d/m/Y'),
                $pedido->franja_inicio ? substr((string) $pedido->franja_inicio, 0, 5).'-'.substr((string) $pedido->franja_fin, 0, 5) : '',
                $pedido->cliente?->nombre,
                $pedido->cliente?->telefono,
                $pedido->direccion_entrega,
                $pedido->comuna,
                $pedido->bultos,
                ExportadorCsv::numero($pedido->peso_kg),
                $pedido->tamano?->getLabel(),
                $pedido->estado->getLabel(),
                $pedido->entrega?->repartidor?->nombre,
                $pedido->comprobante?->codigo,
            ]);

        return ExportadorCsv::descargar(
            'pedidos-'.now(config('app.zona_horaria_negocio'))->format('Y-m-d-His').'.csv',
            ['Pedido', 'Fecha de entrega', 'Franja', 'Cliente', 'Teléfono', 'Dirección', 'Comuna', 'Bultos', 'Peso (kg)', 'Tamaño', 'Estado', 'Repartidor', 'Comprobante'],
            $pedidos,
        );
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

<?php

namespace App\Filament\Resources\Comprobantes\Pages;

use App\Filament\Resources\Comprobantes\ComprobanteResource;
use App\Models\Comprobante;
use App\Services\ExportadorCsv;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListComprobantes extends ListRecords
{
    protected static string $resource = ComprobanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportar')
                ->label('Exportar')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->tooltip('Descarga en CSV (Excel) los comprobantes con la búsqueda y filtros actuales')
                ->action(fn (): StreamedResponse => $this->exportar()),
        ];
    }

    private function exportar(): StreamedResponse
    {
        $zona = config('app.zona_horaria_negocio');

        $comprobantes = $this->getFilteredSortedTableQuery()
            ->lazy(500)
            ->map(fn (Comprobante $comprobante): array => [
                $comprobante->codigo,
                $comprobante->pedido?->codigo,
                $comprobante->pedido?->cliente?->nombre,
                trim($comprobante->pedido?->direccion_entrega.($comprobante->pedido?->comuna ? ', '.$comprobante->pedido->comuna : '')),
                $comprobante->emitido_at?->timezone($zona)->format('d/m/Y H:i'),
                $comprobante->repartidor?->nombre,
                $comprobante->resultado?->getLabel(),
                $comprobante->receptor_nombre,
                $comprobante->motivo_no_entrega,
                $comprobante->enviado_a,
                $comprobante->enviado_cliente_at?->timezone($zona)->format('d/m/Y H:i'),
            ]);

        return ExportadorCsv::descargar(
            'comprobantes-'.now($zona)->format('Y-m-d-His').'.csv',
            ['N°', 'Pedido', 'Cliente', 'Dirección', 'Fecha y hora', 'Repartidor', 'Resultado', 'Recibió', 'Motivo no entrega', 'Enviado a', 'Fecha de envío'],
            $comprobantes,
        );
    }
}

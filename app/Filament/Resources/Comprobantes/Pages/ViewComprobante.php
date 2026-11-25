<?php

namespace App\Filament\Resources\Comprobantes\Pages;

use App\Filament\Resources\Comprobantes\ComprobanteResource;
use App\Models\Comprobante;
use Filament\Resources\Pages\ViewRecord;

class ViewComprobante extends ViewRecord
{
    protected static string $resource = ComprobanteResource::class;

    public function getTitle(): string
    {
        /** @var Comprobante $comprobante */
        $comprobante = $this->getRecord();

        return 'Comprobante '.$comprobante->codigo;
    }

    protected function getHeaderActions(): array
    {
        return [
            ComprobanteResource::accionDocumento(),
            ComprobanteResource::accionEnviar(),
            ComprobanteResource::accionWhatsapp(),
        ];
    }
}

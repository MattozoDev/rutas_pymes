<?php

namespace App\Filament\Resources\Rutas\Pages;

use App\Filament\Resources\Rutas\RutaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRutas extends ListRecords
{
    protected static string $resource = RutaResource::class;

    protected static ?string $title = 'Asignación y rutas';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Asignar entrega'),
        ];
    }
}

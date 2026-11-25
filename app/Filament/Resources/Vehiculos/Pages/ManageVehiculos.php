<?php

namespace App\Filament\Resources\Vehiculos\Pages;

use App\Filament\Resources\Vehiculos\VehiculoResource;
use App\Filament\Widgets\ResumenFlota;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehiculos extends ManageRecords
{
    protected static string $resource = VehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Agregar vehículo'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResumenFlota::class,
        ];
    }
}

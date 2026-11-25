<?php

namespace App\Filament\Resources\Repartidores\Pages;

use App\Filament\Resources\Repartidores\RepartidorResource;
use App\Filament\Resources\Vehiculos\VehiculoResource;
use App\Filament\Widgets\ResumenFlota;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListRepartidores extends ListRecords
{
    protected static string $resource = RepartidorResource::class;

    protected static ?string $title = 'Repartidores y flota';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('vehiculos')
                ->label('Ver vehículos')
                ->icon(Heroicon::OutlinedTruck)
                ->color('gray')
                ->url(VehiculoResource::getUrl('index')),
            CreateAction::make()->label('Agregar repartidor'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResumenFlota::class,
        ];
    }
}

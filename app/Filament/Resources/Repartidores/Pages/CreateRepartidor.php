<?php

namespace App\Filament\Resources\Repartidores\Pages;

use App\Filament\Resources\Repartidores\RepartidorResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRepartidor extends CreateRecord
{
    protected static string $resource = RepartidorResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return RepartidorResource::guardar(null, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

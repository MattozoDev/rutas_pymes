<?php

namespace App\Filament\Resources\Repartidores\Pages;

use App\Filament\Resources\Repartidores\RepartidorResource;
use App\Models\Repartidor;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditRepartidor extends EditRecord
{
    protected static string $resource = RepartidorResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Repartidor $repartidor */
        $repartidor = $this->getRecord();
        $data['email'] = $repartidor->usuario?->email;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Repartidor $record */
        return RepartidorResource::guardar($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

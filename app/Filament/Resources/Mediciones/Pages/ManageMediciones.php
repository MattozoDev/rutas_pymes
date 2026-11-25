<?php

namespace App\Filament\Resources\Mediciones\Pages;

use App\Filament\Resources\Mediciones\MedicionPlanificacionResource;
use App\Filament\Widgets\IndicadoresMetas;
use App\Models\MedicionPlanificacion;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageMediciones extends ManageRecords
{
    protected static string $resource = MedicionPlanificacionResource::class;

    protected static ?string $title = 'Indicadores de planificación';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Registrar medición')
                ->using(function (array $data): Model {
                    $medicion = new MedicionPlanificacion($data);
                    $medicion->registrado_por = auth()->id();
                    $medicion->save();

                    return $medicion;
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            IndicadoresMetas::class,
        ];
    }
}

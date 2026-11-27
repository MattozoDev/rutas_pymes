<?php

namespace App\Filament\Schemas;

use App\Services\Geocodificador;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;

final class UbicacionEnMapa
{
    public static function accionUbicar(string $campoDireccion, string $campoComuna): Action
    {
        return Action::make('ubicarDireccion')
            ->label('Ubicar')
            ->icon(Heroicon::OutlinedMapPin)
            ->tooltip('Buscar la dirección en el mapa')
            ->action(function (Get $get, Set $set) use ($campoDireccion, $campoComuna): void {
                $resultado = Geocodificador::buscar($get($campoDireccion), $get($campoComuna));

                if ($resultado === null) {
                    Notification::make()
                        ->warning()
                        ->title('No encontramos esa dirección')
                        ->body('Revisa calle, número y comuna, o marca el punto directamente en el mapa.')
                        ->send();

                    return;
                }

                $set('latitud', $resultado['lat']);
                $set('longitud', $resultado['lng']);

                Notification::make()->success()->title('Dirección ubicada')->body($resultado['etiqueta'])->send();
            });
    }

    public static function fieldset(): Fieldset
    {
        return Fieldset::make('Ubicación en el mapa')
            ->columns(2)
            ->columnSpanFull()
            ->schema([
                View::make('filament.selector-ubicacion')
                    ->columnSpanFull()
                    ->viewData(fn (Component $component): array => [
                        'rutaLatitud' => $component->getContainer()->getStatePath().'.latitud',
                        'rutaLongitud' => $component->getContainer()->getStatePath().'.longitud',
                    ]),
                TextInput::make('latitud')->numeric()->minValue(-90)->maxValue(90)->placeholder('-33.4489'),
                TextInput::make('longitud')->numeric()->minValue(-180)->maxValue(180)->placeholder('-70.6693'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public static function completarCoordenadas(array $datos, string $campoDireccion, string $campoComuna): array
    {
        if (filled($datos['latitud'] ?? null) && filled($datos['longitud'] ?? null)) {
            return $datos;
        }

        $resultado = Geocodificador::buscar($datos[$campoDireccion] ?? null, $datos[$campoComuna] ?? null);

        if ($resultado !== null) {
            $datos['latitud'] = $resultado['lat'];
            $datos['longitud'] = $resultado['lng'];
        }

        return $datos;
    }
}

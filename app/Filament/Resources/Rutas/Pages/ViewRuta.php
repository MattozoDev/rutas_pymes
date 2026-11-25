<?php

namespace App\Filament\Resources\Rutas\Pages;

use App\Enums\EstadoRuta;
use App\Filament\Resources\Rutas\RutaResource;
use App\Filament\Resources\Rutas\Schemas\RutaInfolist;
use App\Models\Ruta;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewRuta extends ViewRecord
{
    protected static string $resource = RutaResource::class;

    public function getTitle(): string
    {
        /** @var Ruta $ruta */
        $ruta = $this->getRecord();

        return sprintf('Ruta de %s · %s', $ruta->repartidor?->nombre, $ruta->fecha->format('d/m/Y'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('abrirMapa')
                ->label('Abrir en Google Maps')
                ->icon(Heroicon::OutlinedMapPin)
                ->color('gray')
                ->url(fn (Ruta $record): ?string => RutaInfolist::urlMapa($record), shouldOpenInNewTab: true)
                ->visible(fn (Ruta $record): bool => $record->entregas->isNotEmpty()),
            EditAction::make()->label('Editar asignación'),
            ActionGroup::make([
                self::accionDeEstado('iniciar', 'Iniciar ruta', EstadoRuta::EnCurso, Heroicon::OutlinedPlay, 'primary'),
                self::accionDeEstado('completar', 'Marcar completada', EstadoRuta::Completada, Heroicon::OutlinedCheckCircle, 'success'),
                self::accionDeEstado('cancelar', 'Cancelar ruta', EstadoRuta::Cancelada, Heroicon::OutlinedXCircle, 'danger')
                    ->modalDescription('Las entregas que no se han cerrado se eliminan y esos pedidos vuelven a "pendiente".'),
            ])->label('Estado')->button()->color('gray'),
        ];
    }

    private static function accionDeEstado(string $nombre, string $etiqueta, EstadoRuta $estado, Heroicon $icono, string $color): Action
    {
        return Action::make($nombre)
            ->label($etiqueta)
            ->icon($icono)
            ->color($color)
            ->requiresConfirmation()
            ->visible(fn (Ruta $record): bool => $record->estado->puedeCambiarA($estado))
            ->action(function (Ruta $record) use ($estado): void {
                if ($record->cambiarEstado($estado)) {
                    Notification::make()->success()->title('Ruta '.mb_strtolower($estado->getLabel()))->send();

                    return;
                }

                Notification::make()
                    ->warning()
                    ->title('No se puede cambiar el estado')
                    ->body('Una ruta solo se completa cuando todas sus entregas están cerradas.')
                    ->send();
            });
    }
}

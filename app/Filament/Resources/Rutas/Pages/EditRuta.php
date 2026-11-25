<?php

namespace App\Filament\Resources\Rutas\Pages;

use App\Filament\Resources\Rutas\RutaResource;
use App\Models\Ruta;
use App\Services\AsignadorRuta;
use App\Services\CapacidadExcedidaException;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditRuta extends EditRecord
{
    protected static string $resource = RutaResource::class;

    protected static ?string $title = 'Editar asignación';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Ruta $ruta */
        $ruta = $this->getRecord();

        $data['pedidos'] = $ruta->entregas->sortBy('orden_parada')->pluck('pedido_id')->values()->all();
        $data['optimizar'] = false;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Ruta $record */
        try {
            return app(AsignadorRuta::class)->guardar(
                ruta: $record,
                datosRuta: Arr::except($data, ['pedidos', 'optimizar']),
                pedidoIds: array_values($data['pedidos']),
                optimizarOrden: (bool) ($data['optimizar'] ?? false),
            );
        } catch (CapacidadExcedidaException $excepcion) {
            Notification::make()->danger()->title('Capacidad insuficiente')->body($excepcion->getMessage())->persistent()->send();

            throw new Halt;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->label('Eliminar ruta')
                ->modalDescription('Los pedidos de esta ruta volverán a quedar pendientes de asignar.')
                ->using(function (Ruta $record): void {
                    DB::transaction(function () use ($record): void {
                        $record->entregas()->delete();
                        $record->delete();
                    });
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}

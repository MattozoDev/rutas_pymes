<?php

namespace App\Filament\Resources\Pedidos\Tables;

use App\Enums\EstadoPedido;
use App\Filament\Resources\Rutas\RutaResource;
use App\Models\Pedido;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PedidosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_entrega', 'desc')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Pedido')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('numero', $direction)),
                TextColumn::make('fecha_entrega')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('direccion_entrega')
                    ->label('Dirección de entrega')
                    ->description(fn (Pedido $record): ?string => $record->comuna)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('carga')
                    ->label('Carga')
                    ->state(fn (Pedido $record): string => $record->resumenCarga()),
                TextColumn::make('estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('entrega.repartidor.nombre')
                    ->label('Repartidor')
                    ->placeholder('—'),
            ])
            ->filters([
                Filter::make('fecha_entrega')
                    ->label('Fecha de entrega')
                    ->form([
                        DatePicker::make('desde')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('hasta')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, string $fecha) => $q->where('fecha_entrega', '>=', $fecha))
                        ->when($data['hasta'] ?? null, fn (Builder $q, string $fecha) => $q->where('fecha_entrega', '<=', $fecha))),
            ])
            ->recordActions([
                Action::make('asignar')
                    ->label('Asignar')
                    ->icon(Heroicon::OutlinedMap)
                    ->color('primary')
                    ->visible(fn (Pedido $record): bool => $record->esEditable())
                    ->url(fn (Pedido $record): string => RutaResource::getUrl('create', ['pedidos' => $record->id])),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()->visible(fn (Pedido $record): bool => $record->esEditable()),
                    DeleteAction::make()->visible(fn (Pedido $record): bool => $record->esEditable()),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('asignarSeleccionados')
                        ->label('Asignar a una ruta')
                        ->icon(Heroicon::OutlinedMap)
                        ->action(function (Collection $records) {
                            $pendientes = $records->filter(fn (Pedido $pedido): bool => $pedido->esEditable());

                            if ($pendientes->isEmpty()) {
                                Notification::make()->warning()->title('Selecciona al menos un pedido pendiente.')->send();

                                return null;
                            }

                            return redirect(RutaResource::getUrl('create', ['pedidos' => $pendientes->pluck('id')->implode(',')]));
                        }),
                ]),
            ])
            ->emptyStateHeading('Aún no hay pedidos')
            ->emptyStateDescription('Crea el primer pedido para empezar a planificar rutas.');
    }

    /**
     * @return array<string, EstadoPedido|null>
     */
    public static function estadosParaPestanas(): array
    {
        return [
            'todos' => null,
            'pendiente' => EstadoPedido::Pendiente,
            'asignado' => EstadoPedido::Asignado,
            'en_reparto' => EstadoPedido::EnReparto,
            'entregado' => EstadoPedido::Entregado,
            'no_entregado' => EstadoPedido::NoEntregado,
        ];
    }
}

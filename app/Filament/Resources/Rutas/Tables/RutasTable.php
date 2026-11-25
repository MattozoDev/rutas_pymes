<?php

namespace App\Filament\Resources\Rutas\Tables;

use App\Enums\EstadoEntrega;
use App\Enums\EstadoRuta;
use App\Models\Entrega;
use App\Models\Ruta;
use App\Services\CapacidadVehiculo;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RutasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('repartidor.nombre')->label('Repartidor')->searchable(),
                TextColumn::make('vehiculo.identificador')
                    ->label('Vehículo')
                    ->description(fn (Ruta $record): ?string => $record->vehiculo?->nombreTipo()),
                TextColumn::make('paradas')
                    ->label('Entregas')
                    ->state(fn (Ruta $record): string => sprintf(
                        '%d / %d',
                        $record->entregas->whereIn('estado', [EstadoEntrega::Entregada, EstadoEntrega::NoEntregada])->count(),
                        $record->entregas->count(),
                    )),
                TextColumn::make('carga')
                    ->label('Carga')
                    ->state(function (Ruta $record): string {
                        $evaluacion = CapacidadVehiculo::evaluar(
                            $record->vehiculo?->capacidad_kg ?? 0,
                            $record->entregas->map(fn (Entrega $entrega) => $entrega->pedido?->peso_kg),
                        );

                        return sprintf('%s / %s kg (%d %%)', CapacidadVehiculo::formatearKg($evaluacion->cargaKg), CapacidadVehiculo::formatearKg($evaluacion->capacidadKg), $evaluacion->porcentaje());
                    }),
                TextColumn::make('distancia_km')->label('Distancia')->suffix(' km')->placeholder('—'),
                TextColumn::make('duracion_min')->label('Tiempo')->suffix(' min')->placeholder('—'),
                TextColumn::make('estado')->badge(),
            ])
            ->filters([
                SelectFilter::make('estado')->options(EstadoRuta::class),
                Filter::make('fecha')
                    ->form([DatePicker::make('fecha')->native(false)->displayFormat('d/m/Y')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['fecha'] ?? null,
                        fn (Builder $q, string $fecha) => $q->where('fecha', $fecha),
                    )),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalDescription('Los pedidos de esta ruta volverán a quedar pendientes de asignar.')
                        ->using(function (Ruta $record): void {
                            DB::transaction(function () use ($record): void {
                                $record->entregas()->delete();
                                $record->delete();
                            });
                        }),
                ]),
            ])
            ->emptyStateHeading('Sin rutas planificadas')
            ->emptyStateDescription('Asigna pedidos pendientes a un repartidor para crear la primera ruta.');
    }
}

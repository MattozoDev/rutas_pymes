<?php

namespace App\Filament\Resources\Vehiculos;

use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\Vehiculos\Pages\ManageVehiculos;
use App\Models\Entrega;
use App\Models\Vehiculo;
use App\Services\CapacidadVehiculo;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class VehiculoResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = Vehiculo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'vehículo';

    protected static ?string $pluralModelLabel = 'vehículos';

    protected static ?string $recordTitleAttribute = 'identificador';

    /**
     * @return Builder<Vehiculo>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('repartidores');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('tipo')
                    ->options(fn (): array => CapacidadVehiculo::opcionesDeTipo())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (?string $state, Set $set) => $set(
                        'capacidad_kg',
                        $state === null ? null : CapacidadVehiculo::capacidadPorDefecto($state),
                    )),
                TextInput::make('identificador')
                    ->label('Patente / identificador')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('empresa_id', auth()->user()->empresa_id)),
                TextInput::make('capacidad_kg')
                    ->label('Capacidad de carga')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->suffix('kg')
                    ->helperText('Se propone la capacidad del tipo; ajústala si el vehículo real carga menos.'),
                Toggle::make('activo')
                    ->default(true)
                    ->inline(false)
                    ->helperText('Desactívalo si está en mantención: no aparecerá al asignar rutas.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('capacidad_kg')
            ->columns([
                TextColumn::make('tipo')->formatStateUsing(fn (string $state): string => CapacidadVehiculo::nombreDeTipo($state))->sortable(),
                TextColumn::make('identificador')->weight('bold')->searchable(),
                TextColumn::make('capacidad_kg')
                    ->label('Capacidad')
                    ->formatStateUsing(fn (string $state): string => CapacidadVehiculo::formatearKg($state).' kg')
                    ->sortable(),
                TextColumn::make('repartidores.nombre')->label('Repartidor asignado')->placeholder('—'),
                IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('tipo')->options(fn (): array => CapacidadVehiculo::opcionesDeTipo()),
                TernaryFilter::make('activo'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalDescription('Si el vehículo tiene rutas o entregas registradas, se desactiva en lugar de eliminarse para conservar el historial.')
                        ->using(function (Vehiculo $record): void {
                            if ($record->rutas()->exists() || Entrega::query()->whereBelongsTo($record)->exists()) {
                                $record->update(['activo' => false]);

                                return;
                            }

                            $record->delete();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVehiculos::route('/'),
        ];
    }
}

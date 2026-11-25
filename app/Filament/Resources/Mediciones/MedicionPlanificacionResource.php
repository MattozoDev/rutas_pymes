<?php

namespace App\Filament\Resources\Mediciones;

use App\Enums\EscenarioMedicion;
use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\Mediciones\Pages\ManageMediciones;
use App\Models\MedicionPlanificacion;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class MedicionPlanificacionResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = MedicionPlanificacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Registros';

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'medición';

    protected static ?string $pluralModelLabel = 'mediciones';

    protected static ?string $slug = 'indicadores';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                ToggleButtons::make('escenario')
                    ->options(EscenarioMedicion::class)
                    ->inline()
                    ->required()
                    ->helperText('Manual: planificación en cuaderno o "al ojo". Con rutapyme: usando la asignación y la ruta sugerida.')
                    ->columnSpanFull(),
                DatePicker::make('fecha')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(fn (): string => today(config('app.zona_horaria_negocio'))->toDateString()),
                TextInput::make('grupo_comparacion')
                    ->label('Grupo de comparación')
                    ->placeholder('Ej: semana-1, piloto-almacen')
                    ->helperText('Usa el mismo nombre para comparar un día manual con su equivalente en el sistema.')
                    ->maxLength(100),
                TextInput::make('n_pedidos')->label('N° de pedidos')->required()->integer()->minValue(1),
                Select::make('tipo_vehiculo')
                    ->label('Tipo de vehículo')
                    ->options(fn (): array => CapacidadVehiculo::opcionesDeTipo()),
                TextInput::make('tiempo_planificacion_min')
                    ->label('Tiempo de planificación')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('min')
                    ->helperText('Desde que se reciben los pedidos hasta que cada repartidor sabe su ruta.'),
                TextInput::make('km_recorridos')->label('Km recorridos (total)')->numeric()->minValue(0)->suffix('km'),
                TextInput::make('km_motorizados')
                    ->label('Km motorizados')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('km')
                    ->helperText('Km en auto, torito o furgón (excluye bicicleta). Del odómetro o de la app de mapas.'),
                Textarea::make('notas')->maxLength(2000)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('escenario')->badge(),
                TextColumn::make('grupo_comparacion')->label('Grupo')->placeholder('—')->searchable(),
                TextColumn::make('n_pedidos')->label('Pedidos'),
                TextColumn::make('tiempo_planificacion_min')->label('Planificación')->suffix(' min'),
                TextColumn::make('minutos_por_pedido')
                    ->label('Min/pedido')
                    ->state(fn (MedicionPlanificacion $record): string => number_format((float) $record->tiempo_planificacion_min / max(1, $record->n_pedidos), 1, ',', '.')),
                TextColumn::make('km_motorizados')->label('Km motorizados')->suffix(' km')->placeholder('—'),
                TextColumn::make('registrador.name')->label('Registró')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('escenario')->options(EscenarioMedicion::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin mediciones todavía')
            ->emptyStateDescription('Registra al menos un día planificado a mano y uno con rutapyme para validar las metas del proyecto.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMediciones::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Pedidos\Schemas;

use App\Enums\TamanoCarga;
use App\Filament\Schemas\UbicacionEnMapa;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\CapacidadVehiculo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PedidoForm
{
    /**
     * @var list<string>
     */
    public const COMUNAS_FRECUENTES = [
        'Santiago', 'Providencia', 'Ñuñoa', 'Las Condes', 'La Florida', 'Maipú', 'Puente Alto',
        'San Miguel', 'Estación Central', 'Pedro Aguirre Cerda', 'Recoleta', 'Independencia',
        'Macul', 'La Reina', 'Vitacura', 'Lo Barnechea', 'Quinta Normal', 'San Joaquín',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Cliente')
                        ->schema([
                            Select::make('cliente_id')
                                ->label('Buscar cliente')
                                ->relationship('cliente', 'nombre', fn ($query) => $query->activos()->orderBy('nombre'))
                                ->getOptionLabelFromRecordUsing(fn (Cliente $record): string => trim($record->nombre.($record->telefono ? ' — '.$record->telefono : '')))
                                ->searchable(['nombre', 'telefono'])
                                ->preload()
                                ->required()
                                ->live()
                                ->helperText('Se usará la dirección registrada; puedes cambiarla abajo.')
                                ->afterStateUpdated(fn (?string $state, Set $set) => self::copiarDireccionDelCliente($state, $set))
                                ->createOptionForm([
                                    TextInput::make('nombre')->required()->maxLength(255),
                                    TextInput::make('telefono')->tel()->maxLength(30),
                                    TextInput::make('email')->email()->maxLength(255),
                                    TextInput::make('direccion')->label('Dirección')->maxLength(255),
                                    TextInput::make('comuna')->datalist(self::COMUNAS_FRECUENTES)->maxLength(100),
                                ])
                                ->createOptionModalHeading('Cliente nuevo'),
                        ]),

                    Section::make('Datos de entrega')
                        ->columns(2)
                        ->schema([
                            TextInput::make('direccion_entrega')
                                ->label('Dirección de entrega')
                                ->placeholder('Ej: Av. Matta 1240')
                                ->required()
                                ->maxLength(255)
                                ->suffixAction(UbicacionEnMapa::accionUbicar('direccion_entrega', 'comuna'))
                                ->columnSpanFull(),
                            TextInput::make('comuna')
                                ->required()
                                ->datalist(self::COMUNAS_FRECUENTES)
                                ->maxLength(100),
                            TextInput::make('referencia')
                                ->placeholder('Depto, local, portón…')
                                ->maxLength(255),
                            DatePicker::make('fecha_entrega')
                                ->label('Fecha de entrega')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->default(fn (): string => today(config('app.zona_horaria_negocio'))->toDateString()),
                            Fieldset::make('Franja horaria')
                                ->columns(2)
                                ->schema([
                                    TimePicker::make('franja_inicio')->label('Desde')->seconds(false),
                                    TimePicker::make('franja_fin')->label('Hasta')->seconds(false)->after('franja_inicio'),
                                ]),
                            UbicacionEnMapa::fieldset(),
                        ]),

                    Section::make('Carga')
                        ->description('Bultos y peso son obligatorios (RN-02).')
                        ->columns(2)
                        ->schema([
                            TextInput::make('bultos')
                                ->required()
                                ->integer()
                                ->minValue(1)
                                ->default(1)
                                ->live(onBlur: true),
                            TextInput::make('peso_kg')
                                ->label('Peso total (kg)')
                                ->required()
                                ->numeric()
                                ->minValue(0.01)
                                ->maxValue(999999)
                                ->suffix('kg')
                                ->live(onBlur: true)
                                ->helperText('Se usa para validar la capacidad del vehículo.'),
                            Select::make('tamano')
                                ->label('Tamaño')
                                ->options(TamanoCarga::class)
                                ->default(TamanoCarga::Mediano)
                                ->required()
                                ->helperText('Referencial.'),
                            TextInput::make('descripcion_carga')
                                ->label('Descripción')
                                ->placeholder('Ej: cajas medianas')
                                ->maxLength(1000),
                            Textarea::make('observaciones')
                                ->placeholder('Ej: producto frágil, llamar antes de llegar')
                                ->maxLength(2000)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Resumen')
                        ->schema([
                            Text::make(fn (?Pedido $record): string => 'N° de pedido: '.($record?->codigo ?? 'automático al guardar')),
                            Text::make(fn (?Pedido $record): string => $record?->estado->getLabel() ?? 'Pendiente')
                                ->badge()
                                ->color(fn (?Pedido $record): string => $record?->estado->getColor() ?? 'warning'),
                            Text::make(fn (Get $get): string => sprintf(
                                'Carga: %s %s · %s kg',
                                (int) $get('bultos'),
                                (int) $get('bultos') === 1 ? 'bulto' : 'bultos',
                                CapacidadVehiculo::formatearKg(is_numeric($get('peso_kg')) ? $get('peso_kg') : 0),
                            )),
                        ]),
                    Callout::make('Capacidad de vehículos')
                        ->icon(Heroicon::OutlinedTruck)
                        ->info()
                        ->description(fn (Get $get): string => CapacidadVehiculo::recomendacionParaPeso($get('peso_kg'))
                            ?? 'Ingresa el peso para ver qué vehículos pueden llevar este pedido.'),
                ])->columnSpan(1),
            ]);
    }

    private static function copiarDireccionDelCliente(?string $clienteId, Set $set): void
    {
        if ($clienteId === null) {
            return;
        }

        $cliente = Cliente::query()->find($clienteId);

        if ($cliente === null) {
            return;
        }

        $set('direccion_entrega', $cliente->direccion);
        $set('comuna', $cliente->comuna);
        $set('latitud', $cliente->latitud);
        $set('longitud', $cliente->longitud);
    }
}

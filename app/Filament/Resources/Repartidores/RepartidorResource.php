<?php

namespace App\Filament\Resources\Repartidores;

use App\Enums\EstadoEntrega;
use App\Enums\EstadoRepartidor;
use App\Enums\RolUsuario;
use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\Repartidores\Pages\CreateRepartidor;
use App\Filament\Resources\Repartidores\Pages\EditRepartidor;
use App\Filament\Resources\Repartidores\Pages\ListRepartidores;
use App\Models\Repartidor;
use App\Models\User;
use App\Models\Vehiculo;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class RepartidorResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = Repartidor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?string $navigationLabel = 'Repartidores y flota';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'repartidor';

    protected static ?string $pluralModelLabel = 'repartidores';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $slug = 'repartidores';

    /**
     * @return Builder<Repartidor>
     */
    public static function getEloquentQuery(): Builder
    {
        $hoy = today(config('app.zona_horaria_negocio'))->toDateString();
        $deHoy = fn (Builder $query) => $query->whereHas('ruta', fn (Builder $ruta) => $ruta->where('fecha', $hoy));

        return parent::getEloquentQuery()
            ->with(['vehiculo', 'usuario'])
            ->withCount([
                'entregas as entregas_hoy' => $deHoy,
                'entregas as cerradas_hoy' => fn (Builder $query) => $deHoy($query)
                    ->whereIn('estado', [EstadoEntrega::Entregada, EstadoEntrega::NoEntregada]),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Repartidor')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nombre')->required()->maxLength(255),
                        TextInput::make('telefono')->label('Teléfono')->tel()->placeholder('+56 9 5555 0101')->maxLength(30),
                        Select::make('vehiculo_id')
                            ->label('Vehículo asignado')
                            ->options(fn (): array => Vehiculo::query()
                                ->activos()

                                ->orderBy('capacidad_kg')
                                ->get()
                                ->mapWithKeys(fn (Vehiculo $vehiculo): array => [$vehiculo->id => $vehiculo->etiqueta()])
                                ->all())
                            ->searchable()
                            ->placeholder('Sin vehículo'),
                        Select::make('estado')
                            ->options(EstadoRepartidor::class)
                            ->default(EstadoRepartidor::Disponible)
                            ->required(),
                    ]),
                Section::make('Acceso a la app de repartidor')
                    ->description('Opcional. Con estos datos el repartidor ve sus paradas y confirma entregas desde el teléfono.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignorable: fn (?Repartidor $record): ?User => $record?->usuario)
                            ->requiredWith('password'),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->rule(Password::min(8))
                            ->helperText(fn (?Repartidor $record): string => $record?->usuario ? 'Déjala vacía para mantener la actual.' : 'Mínimo 8 caracteres.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nombre')
            ->columns([
                TextColumn::make('nombre')->label('Repartidor')->weight('bold')->searchable()->sortable(),
                TextColumn::make('telefono')->label('Teléfono')->placeholder('—'),
                TextColumn::make('vehiculo')
                    ->label('Vehículo asignado')
                    ->state(fn (Repartidor $record): ?string => $record->vehiculo?->etiqueta())
                    ->placeholder('—'),
                TextColumn::make('entregas_hoy')
                    ->label('Entregas hoy')
                    ->formatStateUsing(fn (Repartidor $record): string => "{$record->cerradas_hoy} / {$record->entregas_hoy}"),
                TextColumn::make('estado')->badge(),
                TextColumn::make('usuario.email')->label('Acceso app')->placeholder('Sin acceso')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')->options(EstadoRepartidor::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalDescription('Si tiene rutas o entregas registradas, queda inactivo (y sin acceso) en lugar de eliminarse.')
                        ->using(function (Repartidor $record): void {
                            DB::transaction(function () use ($record): void {
                                if ($record->rutas()->exists() || $record->entregas()->exists()) {
                                    $record->update(['estado' => EstadoRepartidor::Inactivo]);
                                    $record->usuario?->update(['activo' => false]);

                                    return;
                                }

                                $usuario = $record->usuario;
                                $record->delete();
                                $usuario?->delete();
                            });
                        }),
                ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function guardar(?Repartidor $repartidor, array $datos): Repartidor
    {
        return DB::transaction(function () use ($repartidor, $datos): Repartidor {
            $repartidor ??= new Repartidor;
            $repartidor->fill(Arr::except($datos, ['email', 'password']));

            if ($repartidor->usuario !== null) {
                $repartidor->usuario->fill(array_filter([
                    'name' => $datos['nombre'],
                    'telefono' => $datos['telefono'] ?? null,
                    'email' => $datos['email'] ?? null,
                    'password' => $datos['password'] ?? null,
                ]))->save();
            } elseif (filled($datos['email'] ?? null) && filled($datos['password'] ?? null)) {
                $repartidor->user_id = User::query()->create([
                    'name' => $datos['nombre'],
                    'email' => $datos['email'],
                    'password' => $datos['password'],
                    'telefono' => $datos['telefono'] ?? null,
                    'empresa_id' => auth()->user()->empresa_id,
                    'rol' => RolUsuario::Repartidor,
                ])->id;
            }

            $repartidor->save();

            return $repartidor;
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRepartidores::route('/'),
            'create' => CreateRepartidor::route('/create'),
            'edit' => EditRepartidor::route('/{record}/edit'),
        ];
    }
}

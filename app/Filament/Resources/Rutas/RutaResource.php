<?php

namespace App\Filament\Resources\Rutas;

use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\Rutas\Pages\CreateRuta;
use App\Filament\Resources\Rutas\Pages\EditRuta;
use App\Filament\Resources\Rutas\Pages\ListRutas;
use App\Filament\Resources\Rutas\Pages\ViewRuta;
use App\Filament\Resources\Rutas\Schemas\RutaForm;
use App\Filament\Resources\Rutas\Schemas\RutaInfolist;
use App\Filament\Resources\Rutas\Tables\RutasTable;
use App\Models\Ruta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RutaResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = Ruta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?string $navigationLabel = 'Asignación y rutas';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'ruta';

    protected static ?string $pluralModelLabel = 'rutas';

    /**
     * @return Builder<Ruta>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'repartidor',
            'vehiculo',
            'entregas.pedido.cliente',
            'entregas.comprobante',
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return RutaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RutaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RutasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRutas::route('/'),
            'create' => CreateRuta::route('/create'),
            'view' => ViewRuta::route('/{record}'),
            'edit' => EditRuta::route('/{record}/edit'),
        ];
    }
}

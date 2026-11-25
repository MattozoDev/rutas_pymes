<?php

namespace App\Filament\Resources\Pedidos;

use App\Enums\EstadoPedido;
use App\Filament\Concerns\SoloAdministradores;
use App\Filament\Resources\Pedidos\Pages\CreatePedido;
use App\Filament\Resources\Pedidos\Pages\EditPedido;
use App\Filament\Resources\Pedidos\Pages\ListPedidos;
use App\Filament\Resources\Pedidos\Pages\ViewPedido;
use App\Filament\Resources\Pedidos\Schemas\PedidoForm;
use App\Filament\Resources\Pedidos\Schemas\PedidoInfolist;
use App\Filament\Resources\Pedidos\Tables\PedidosTable;
use App\Models\Pedido;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PedidoResource extends Resource
{
    use SoloAdministradores;

    protected static ?string $model = Pedido::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'pedido';

    protected static ?string $pluralModelLabel = 'pedidos';

    protected static ?string $recordTitleAttribute = 'codigo';

    public static function getNavigationBadge(): ?string
    {
        $pendientes = (int) (Pedido::resumenPorEstado()->get(EstadoPedido::Pendiente->value)?->total ?? 0);

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Pedidos pendientes de asignar';
    }

    /**
     * @return Builder<Pedido>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['cliente', 'entrega.repartidor']);
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['codigo', 'direccion_entrega', 'cliente.nombre'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Pedido $record */
        return [
            'Cliente' => $record->cliente?->nombre,
            'Estado' => $record->estado->getLabel(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return PedidoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PedidoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PedidosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPedidos::route('/'),
            'create' => CreatePedido::route('/create'),
            'view' => ViewPedido::route('/{record}'),
            'edit' => EditPedido::route('/{record}/edit'),
        ];
    }
}

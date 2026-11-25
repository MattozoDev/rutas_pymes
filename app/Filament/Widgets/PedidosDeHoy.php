<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoPedido;
use App\Filament\Resources\Pedidos\PedidoResource;
use App\Filament\Resources\Rutas\RutaResource;
use App\Models\Pedido;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PedidosDeHoy extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?bool $isLazyLoaded = false;

    protected static ?string $heading = 'Pedidos de hoy';

    protected int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Pedido::query()
                ->with(['cliente', 'entrega.repartidor', 'entrega.vehiculo'])
                ->where('fecha_entrega', today(config('app.zona_horaria_negocio'))->toDateString())
                ->orderBy('numero'))
            ->columns([
                TextColumn::make('codigo')->label('Pedido')->weight('bold'),
                TextColumn::make('cliente.nombre')->label('Cliente')->wrap(),
                TextColumn::make('direccion_entrega')->label('Dirección')
                    ->description(fn (Pedido $record): ?string => $record->comuna)
                    ->wrap(),
                TextColumn::make('estado')->badge(),
                TextColumn::make('entrega.repartidor.nombre')->label('Repartidor')->placeholder('—'),
                TextColumn::make('entrega.vehiculo.identificador')->label('Vehículo')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('estado')->options(EstadoPedido::class),
            ])
            ->recordActions([
                Action::make('asignar')
                    ->label('Asignar')
                    ->button()
                    ->size('sm')
                    ->visible(fn (Pedido $record): bool => $record->estado === EstadoPedido::Pendiente)
                    ->url(fn (Pedido $record): string => RutaResource::getUrl('create', ['pedidos' => $record->id])),
                Action::make('ver')
                    ->label('Ver')
                    ->link()
                    ->visible(fn (Pedido $record): bool => $record->estado !== EstadoPedido::Pendiente)
                    ->url(fn (Pedido $record): string => PedidoResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No hay pedidos para hoy')
            ->paginated([10, 25]);
    }
}

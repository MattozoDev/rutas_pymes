<?php

namespace App\Filament\Resources\Comprobantes\Tables;

use App\Enums\ResultadoEntrega;
use App\Filament\Resources\Comprobantes\ComprobanteResource;
use App\Models\Comprobante;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ComprobantesTable
{
    public static function configure(Table $table): Table
    {
        $zona = config('app.zona_horaria_negocio');

        return $table
            ->defaultSort('emitido_at', 'desc')
            ->columns([
                TextColumn::make('codigo')
                    ->label('N°')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('pedido.codigo')->label('Pedido')->searchable(),
                TextColumn::make('pedido.cliente.nombre')->label('Cliente')->searchable()->wrap(),
                TextColumn::make('emitido_at')
                    ->label('Fecha y hora')
                    ->dateTime('d/m/Y · H:i', $zona)
                    ->sortable(),
                TextColumn::make('repartidor.nombre')->label('Repartidor'),
                TextColumn::make('resultado')->badge(),
                IconColumn::make('enviado_cliente_at')
                    ->label('Enviado')
                    ->state(fn (Comprobante $record): bool => $record->enviado_cliente_at !== null)
                    ->boolean()
                    ->tooltip(fn (Comprobante $record): ?string => $record->enviado_a),
            ])
            ->filters([
                Filter::make('emitido_at')
                    ->label('Rango de fechas')
                    ->form([
                        DatePicker::make('desde')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('hasta')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, string $fecha) => $q->where('emitido_at', '>=', Carbon::parse($fecha, $zona)->startOfDay()->utc()))
                        ->when($data['hasta'] ?? null, fn (Builder $q, string $fecha) => $q->where('emitido_at', '<=', Carbon::parse($fecha, $zona)->endOfDay()->utc()))),
                SelectFilter::make('resultado')->options(ResultadoEntrega::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    ComprobanteResource::accionDocumento(),
                    ComprobanteResource::accionEnviar(),
                    ComprobanteResource::accionWhatsapp(),
                ]),
            ])
            ->emptyStateHeading('Aún no hay comprobantes')
            ->emptyStateDescription('Se emiten automáticamente cuando un repartidor confirma o rechaza una entrega.');
    }
}

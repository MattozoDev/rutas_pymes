<?php

namespace App\Filament\Resources\Clientes\Tables;

use App\Models\Cliente;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nombre')
            ->columns([
                TextColumn::make('nombre')->label('Cliente')->weight('bold')->searchable()->sortable(),
                TextColumn::make('telefono')->label('Teléfono')->searchable()->placeholder('—'),
                TextColumn::make('comuna')->searchable()->sortable()->placeholder('—'),
                TextColumn::make('pedidos_count')->label('Pedidos')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('activo')->default(true),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalDescription('Si el cliente tiene pedidos registrados, se desactiva en lugar de eliminarse para conservar el historial.')
                        ->using(function (Cliente $record): void {
                            if ($record->pedidos()->exists()) {
                                $record->update(['activo' => false]);

                                return;
                            }

                            $record->delete();
                        }),
                ]),
            ])
            ->emptyStateHeading('Aún no hay clientes');
    }
}

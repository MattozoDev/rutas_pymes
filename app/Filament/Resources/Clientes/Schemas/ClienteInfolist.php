<?php

namespace App\Filament\Resources\Clientes\Schemas;

use App\Models\Cliente;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClienteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make(fn (Cliente $record): string => $record->nombre)
                    ->description(fn (Cliente $record): string => 'Cliente desde '.$record->created_at?->locale('es')->isoFormat('MMM YYYY'))
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('telefono')->label('Teléfono')->placeholder('—'),
                        TextEntry::make('email')->label('Correo')->placeholder('—'),
                        TextEntry::make('direccion')
                            ->label('Dirección')
                            ->formatStateUsing(fn (Cliente $record): string => trim($record->direccion.($record->comuna ? ', '.$record->comuna : '')))
                            ->placeholder('—'),
                        TextEntry::make('pedidos_count')->label('Pedidos totales'),
                        TextEntry::make('notas')->placeholder('Sin notas'),
                    ]),
                Section::make('Últimos pedidos')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('ultimosPedidos')
                            ->hiddenLabel()
                            ->state(fn (Cliente $record) => $record->pedidos()->latest('fecha_entrega')->limit(5)->get())
                            ->columns(4)
                            ->placeholder('Sin pedidos')
                            ->schema([
                                TextEntry::make('codigo')->label('Pedido')->weight('bold'),
                                TextEntry::make('fecha_entrega')->label('Fecha')->date('d/m/Y'),
                                TextEntry::make('peso_kg')->label('Peso')->suffix(' kg'),
                                TextEntry::make('estado')->badge(),
                            ]),
                    ]),
            ]);
    }
}

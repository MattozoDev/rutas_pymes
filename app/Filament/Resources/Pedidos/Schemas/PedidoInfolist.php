<?php

namespace App\Filament\Resources\Pedidos\Schemas;

use App\Models\Pedido;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PedidoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Pedido')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('cliente.nombre')->label('Cliente'),
                        TextEntry::make('cliente.telefono')->label('Teléfono')->placeholder('—'),
                        TextEntry::make('direccion_entrega')
                            ->label('Dirección de entrega')
                            ->formatStateUsing(fn (Pedido $record): string => trim($record->direccion_entrega.($record->comuna ? ', '.$record->comuna : ''))),
                        TextEntry::make('referencia')->placeholder('—'),
                        TextEntry::make('fecha_entrega')->label('Fecha')->date('d/m/Y'),
                        TextEntry::make('franja')
                            ->label('Franja horaria')
                            ->state(fn (Pedido $record): ?string => $record->franja_inicio
                                ? substr((string) $record->franja_inicio, 0, 5).' – '.substr((string) $record->franja_fin, 0, 5)
                                : null)
                            ->placeholder('Sin franja'),
                        TextEntry::make('carga')->state(fn (Pedido $record): string => $record->resumenCarga()),
                        TextEntry::make('tamano')->label('Tamaño'),
                        TextEntry::make('descripcion_carga')->label('Descripción')->placeholder('—'),
                        TextEntry::make('estado')->badge(),
                        TextEntry::make('observaciones')->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('Entrega')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('entrega.repartidor.nombre')->label('Repartidor')->placeholder('Sin asignar'),
                        TextEntry::make('entrega.vehiculo.identificador')->label('Vehículo')->placeholder('—'),
                        TextEntry::make('entrega.orden_parada')->label('Parada N°')->placeholder('—'),
                        TextEntry::make('entrega.estado')->label('Estado de la entrega')->badge()->placeholder('—'),
                        TextEntry::make('comprobante.codigo')->label('Comprobante')->placeholder('Se emite al cerrar la entrega'),
                    ]),
            ]);
    }
}

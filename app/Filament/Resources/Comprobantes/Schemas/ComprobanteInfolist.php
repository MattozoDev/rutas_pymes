<?php

namespace App\Filament\Resources\Comprobantes\Schemas;

use App\Models\Comprobante;
use App\Services\ComprobanteDigital;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ComprobanteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $zona = config('app.zona_horaria_negocio');

        return $schema
            ->columns(1)
            ->components([
                Section::make('Comprobante de entrega')
                    ->description(fn (Comprobante $record): string => 'Emitido: '.$record->emitido_at?->timezone($zona)->format('d/m/Y · H:i'))
                    ->schema([
                        Grid::make(3)->schema([
                            Section::make('Emisor')
                                ->compact()
                                ->schema([
                                    TextEntry::make('empresa.nombre')->hiddenLabel()->weight('bold'),
                                    TextEntry::make('empresa.rut')->hiddenLabel()->prefix('RUT ')->placeholder('Despacho propio'),
                                    TextEntry::make('empresa.comuna')->hiddenLabel()->color('gray')->placeholder('—'),
                                ]),
                            Section::make('Destinatario')
                                ->compact()
                                ->schema([
                                    TextEntry::make('pedido.cliente.nombre')->hiddenLabel()->weight('bold'),
                                    TextEntry::make('pedido.direccion_entrega')
                                        ->hiddenLabel()
                                        ->formatStateUsing(fn (Comprobante $record): string => trim($record->pedido->direccion_entrega.($record->pedido->comuna ? ', '.$record->pedido->comuna : ''))),
                                    TextEntry::make('receptor_nombre')->hiddenLabel()->prefix('Recibió: ')->color('gray')->placeholder('Sin receptor'),
                                ]),
                            Section::make('Detalle')
                                ->compact()
                                ->schema([
                                    TextEntry::make('codigo')->hiddenLabel()->prefix('Comprobante ')->weight('bold'),
                                    TextEntry::make('pedido.codigo')->hiddenLabel()->prefix('Pedido: '),
                                    TextEntry::make('repartidor.nombre')->hiddenLabel()->prefix('Repartidor: ')->color('gray'),
                                ]),
                        ]),
                        Grid::make(4)->schema([
                            TextEntry::make('carga')->state(fn (Comprobante $record): ?string => $record->pedido?->resumenCarga()),
                            TextEntry::make('pedido.descripcion_carga')->label('Descripción')->placeholder(fn (Comprobante $record): ?string => $record->pedido?->tamano?->getLabel()),
                            TextEntry::make('vehiculo')->label('Vehículo')->state(fn (Comprobante $record): ?string => $record->entrega?->vehiculo?->etiqueta())->placeholder('—'),
                            TextEntry::make('resultado')->badge(),
                        ]),
                        Grid::make(3)->schema([
                            ImageEntry::make('evidencia')
                                ->label('Foto de evidencia')
                                ->state(fn (Comprobante $record): ?string => app(ComprobanteDigital::class)->urlEvidencia($record))
                                ->imageHeight(180)
                                ->placeholder('Sin foto adjunta'),
                            Grid::make(1)->columnSpan(2)->schema([
                                TextEntry::make('motivo_no_entrega')
                                    ->label('Motivo de no entrega')
                                    ->visible(fn (Comprobante $record): bool => filled($record->motivo_no_entrega)),
                                TextEntry::make('observaciones')->placeholder('Entrega recibida conforme.'),
                                TextEntry::make('enviado_cliente_at')
                                    ->label('Envío al cliente')
                                    ->dateTime('d/m/Y H:i', $zona)
                                    ->suffix(fn (Comprobante $record): string => $record->enviado_a ? ' · '.$record->enviado_a : '')
                                    ->placeholder('Aún no se envía'),
                                TextEntry::make('verificacion')
                                    ->label('Código de verificación')
                                    ->state(fn (Comprobante $record): string => app(ComprobanteDigital::class)->codigoVerificacion($record))
                                    ->fontFamily('mono')
                                    ->copyable(),
                                TextEntry::make('enlace')
                                    ->label('Enlace público para el cliente')
                                    ->state(fn (Comprobante $record): string => app(ComprobanteDigital::class)->urlPublica($record))
                                    ->limit(60)
                                    ->copyable()
                                    ->copyMessage('Enlace copiado'),
                            ]),
                        ]),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Rutas\Schemas;

use App\Filament\Resources\Comprobantes\ComprobanteResource;
use App\Models\Entrega;
use App\Models\Ruta;
use App\Services\CapacidadVehiculo;
use App\Services\MapaEntregas;
use App\Services\PlanificadorRuta;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class RutaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Paradas en orden de visita')
                    ->columnSpan(2)
                    ->schema([
                        View::make('filament.mapa-entregas')
                            ->viewData(fn (Ruta $record): array => [
                                'mapa' => MapaEntregas::paraEntregas($record->entregas, $record->vehiculo?->tipo),
                                'altura' => '20rem',
                                'indicaciones' => true,
                            ]),
                        RepeatableEntry::make('entregas')
                            ->hiddenLabel()
                            ->columns(6)
                            ->schema([
                                TextEntry::make('orden_parada')->label('N°')->weight('bold'),
                                TextEntry::make('pedido.codigo')->label('Pedido'),
                                TextEntry::make('pedido.cliente.nombre')->label('Cliente'),
                                TextEntry::make('pedido.direccion_entrega')
                                    ->label('Dirección')
                                    ->columnSpan(2)
                                    ->formatStateUsing(fn (Entrega $record): string => trim($record->pedido->direccion_entrega.($record->pedido->comuna ? ', '.$record->pedido->comuna : ''))),
                                TextEntry::make('estado')
                                    ->badge()
                                    ->helperText(fn (Entrega $record): ?string => $record->comprobante?->codigo)
                                    ->url(fn (Entrega $record): ?string => $record->comprobante
                                        ? ComprobanteResource::getUrl('view', ['record' => $record->comprobante])
                                        : null),
                            ]),
                    ]),
                Section::make('Resumen')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('estado')->badge(),
                        TextEntry::make('repartidor.nombre')->label('Repartidor'),
                        TextEntry::make('vehiculo')
                            ->label('Vehículo')
                            ->state(fn (Ruta $record): ?string => $record->vehiculo?->etiqueta()),
                        TextEntry::make('carga')
                            ->label('Capacidad')
                            ->state(fn (Ruta $record): string => CapacidadVehiculo::evaluar(
                                $record->vehiculo?->capacidad_kg ?? 0,
                                $record->entregas->map(fn (Entrega $entrega) => $entrega->pedido?->peso_kg),
                            )->mensaje()),
                        TextEntry::make('distancia_km')
                            ->label('Distancia')
                            ->suffix(fn (Ruta $record): string => ($record->trayecto['calculo'] ?? null) === 'calles' ? ' km por calles' : ' km en línea recta')
                            ->placeholder('Faltan coordenadas'),
                        TextEntry::make('duracion_min')
                            ->label('Tiempo estimado')
                            ->suffix(' min (incluye 5 min por parada)')
                            ->placeholder('—'),
                        TextEntry::make('ahorro')
                            ->label('Ahorro vs. orden ingresado')
                            ->state(fn (Ruta $record): ?string => self::ahorroKm($record))
                            ->placeholder('—'),
                        TextEntry::make('observaciones')->placeholder('Sin observaciones'),
                    ]),
            ]);
    }

    public static function ahorroKm(Ruta $ruta): ?string
    {
        $ingresado = $ruta->trayecto['distancia_orden_ingresado_km'] ?? null;

        if ($ingresado === null || $ruta->distancia_km === null) {
            return null;
        }

        $ahorro = (float) $ingresado - (float) $ruta->distancia_km;

        return $ahorro > 0.05
            ? sprintf('%s km (%s %%)', number_format($ahorro, 1, ',', '.'), number_format($ahorro / (float) $ingresado * 100, 0))
            : null;
    }

    public static function urlMapa(Ruta $ruta): ?string
    {
        $destinos = $ruta->entregas
            ->sortBy('orden_parada')
            ->map(fn (Entrega $entrega): string => $entrega->pedido->latitud !== null
                ? $entrega->pedido->latitud.','.$entrega->pedido->longitud
                : trim($entrega->pedido->direccion_entrega.', '.$entrega->pedido->comuna.', Chile', ', '))
            ->values()
            ->all();

        return PlanificadorRuta::urlGoogleMaps($destinos, null, $ruta->vehiculo?->tipo);
    }
}

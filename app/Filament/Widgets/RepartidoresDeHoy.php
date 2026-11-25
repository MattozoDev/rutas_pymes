<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoRepartidor;
use App\Models\Repartidor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RepartidoresDeHoy extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?bool $isLazyLoaded = false;

    protected static ?string $heading = 'Repartidores hoy';

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Repartidor::query()
                ->with('vehiculo')
                ->where('estado', '!=', EstadoRepartidor::Inactivo)
                ->orderByRaw("case estado when 'en_ruta' then 0 else 1 end")
                ->orderBy('nombre'))
            ->columns([
                TextColumn::make('nombre')
                    ->weight('bold')
                    ->description(fn (Repartidor $record): string => $record->vehiculo?->etiqueta() ?? 'Sin vehículo'),
                TextColumn::make('estado')->badge()->alignEnd(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Sin repartidores activos');
    }
}

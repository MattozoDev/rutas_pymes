<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoPedido;
use App\Filament\Resources\Pedidos\PedidoResource;
use App\Filament\Resources\Rutas\RutaResource;
use App\Models\Pedido;
use App\Services\CapacidadVehiculo;
use App\Services\MetricasPanel;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PorAsignar extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?bool $isLazyLoaded = false;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    private const LIMITE = 8;

    public function table(Table $table): Table
    {
        $pendientes = MetricasPanel::obtener()['pendientes'];

        return $table
            ->heading('Por asignar')
            ->description($pendientes > self::LIMITE
                ? 'Los '.self::LIMITE." más urgentes de {$pendientes} pedidos pendientes"
                : 'Pedidos pendientes, los más urgentes primero')
            ->query(fn () => Pedido::query()
                ->with('cliente')
                ->where('estado', EstadoPedido::Pendiente)
                ->orderBy('fecha_entrega')
                ->orderBy('franja_inicio')
                ->orderBy('numero')
                ->limit(self::LIMITE))
            ->paginated(false)
            ->columns([
                TextColumn::make('urgencia')
                    ->label('Pedido')
                    ->badge()
                    ->state(fn (Pedido $record): string => self::urgencia($record->fecha_entrega)[0])
                    ->color(fn (Pedido $record): string => self::urgencia($record->fecha_entrega)[1])
                    ->description(fn (Pedido $record): string => $record->codigo.' · '.$record->fecha_entrega->format('d/m')
                        .($record->franja_inicio ? ' · '.substr((string) $record->franja_inicio, 0, 5) : '')),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->weight('medium')
                    ->description(fn (Pedido $record): string => Str::limit(trim($record->direccion_entrega.($record->comuna ? ', '.$record->comuna : '')), 34)),
                TextColumn::make('carga')
                    ->label('Carga')
                    ->state(fn (Pedido $record): string => $record->resumenCarga())
                    ->description(fn (Pedido $record): string => 'Mín.: '.self::vehiculoMinimo($record)),
            ])
            ->recordActions([
                Action::make('asignar')
                    ->label('Asignar')
                    ->icon(Heroicon::OutlinedMap)
                    ->iconButton()
                    ->color('primary')
                    ->tooltip('Asignar a una ruta')
                    ->url(fn (Pedido $record): string => RutaResource::getUrl('create', ['pedidos' => $record->id])),
            ])
            ->headerActions([
                Action::make('verTodos')
                    ->label('Ver todos')
                    ->link()
                    ->icon(Heroicon::ArrowRight)
                    ->iconPosition('after')
                    ->url(PedidoResource::getUrl('index', ['tab' => 'pendiente'])),
            ])
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->emptyStateHeading('Todo asignado')
            ->emptyStateDescription('No hay pedidos pendientes. Los nuevos pedidos aparecerán aquí.')
            ->emptyStateActions([
                Action::make('nuevo')
                    ->label('Nuevo pedido')
                    ->icon(Heroicon::Plus)
                    ->url(PedidoResource::getUrl('create')),
            ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function urgencia(Carbon $fecha): array
    {
        $dias = (int) today(config('app.zona_horaria_negocio'))->startOfDay()->diffInDays($fecha->copy()->startOfDay(), false);

        return match (true) {
            $dias < 0 => [($dias === -1 ? 'Atrasado 1 día' : 'Atrasado '.abs($dias).' días'), 'danger'],
            $dias === 0 => ['Hoy', 'warning'],
            $dias === 1 => ['Mañana', 'info'],
            default => ['En '.$dias.' días', 'gray'],
        };
    }

    private static function vehiculoMinimo(Pedido $pedido): string
    {
        $capacidades = CapacidadVehiculo::tiposDeVehiculo()->map(fn ($tipo): string => (string) $tipo->capacidad_kg_defecto)->all();
        $codigo = CapacidadVehiculo::tipoMinimo($pedido->peso_kg, $capacidades);

        return $codigo === null ? 'excede la flota' : CapacidadVehiculo::nombreDeTipo($codigo);
    }
}

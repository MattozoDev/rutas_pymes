<?php

namespace App\Filament\Resources\Rutas\Schemas;

use App\Enums\EstadoPedido;
use App\Enums\EstadoRepartidor;
use App\Models\Pedido;
use App\Models\Repartidor;
use App\Models\Ruta;
use App\Models\Vehiculo;
use App\Services\AsignadorRuta;
use App\Services\CapacidadVehiculo;
use App\Services\EvaluacionCapacidad;
use App\Services\PlanificadorRuta;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class RutaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(5)
            ->components([
                Group::make([
                    Section::make('Pedidos de la ruta')
                        ->schema([
                            DatePicker::make('fecha')
                                ->label('Fecha de reparto')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->default(fn (): string => today(config('app.zona_horaria_negocio'))->toDateString())
                                ->live(),
                            Select::make('pedidos')
                                ->label('Pedidos')
                                ->multiple()
                                ->required()
                                ->searchable()
                                ->live()
                                ->options(fn (Get $get, ?Ruta $record): array => self::opcionesDePedidos($get('fecha'), $record))
                                ->helperText('Pedidos pendientes hasta la fecha de reparto. El orden de visita se calcula al guardar.')
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        $evaluacion = self::evaluarCapacidad($get('vehiculo_id'), (array) $value);

                                        if ($evaluacion?->excede()) {
                                            $fail(sprintf(
                                                'La suma de los pedidos (%s kg) supera la capacidad del vehículo (%s kg). No se permite guardar.',
                                                CapacidadVehiculo::formatearKg($evaluacion->cargaKg),
                                                CapacidadVehiculo::formatearKg($evaluacion->capacidadKg),
                                            ));
                                        }
                                    },
                                ]),
                        ]),

                    Section::make('Repartidor y vehículo')
                        ->schema([
                            Select::make('repartidor_id')
                                ->label('Repartidor')
                                ->required()
                                ->searchable()
                                ->live()
                                ->options(fn (): array => self::opcionesDeRepartidores())
                                ->afterStateUpdated(function (?string $state, Set $set): void {
                                    $vehiculoId = $state === null ? null : Repartidor::query()->whereKey($state)->value('vehiculo_id');

                                    if ($vehiculoId !== null) {
                                        $set('vehiculo_id', $vehiculoId);
                                    }
                                }),
                            Select::make('vehiculo_id')
                                ->label('Vehículo')
                                ->required()
                                ->live()
                                ->options(fn (): array => Vehiculo::query()
                                    ->activos()

                                    ->orderBy('capacidad_kg')
                                    ->get()
                                    ->mapWithKeys(fn (Vehiculo $vehiculo): array => [$vehiculo->id => $vehiculo->etiqueta()])
                                    ->all())
                                ->helperText('Se completa con el vehículo asociado al repartidor.'),
                            Callout::make(fn (Get $get): string => self::evaluarCapacidad($get('vehiculo_id'), (array) $get('pedidos'))?->mensaje()
                                ?? 'Selecciona pedidos y vehículo para validar la capacidad')
                                ->status(fn (Get $get): string => match (self::evaluarCapacidad($get('vehiculo_id'), (array) $get('pedidos'))?->excede()) {
                                    true => 'danger',
                                    false => 'success',
                                    null => 'info',
                                })
                                ->description(fn (Get $get): string => self::detalleDeCarga((array) $get('pedidos'))),
                            Toggle::make('optimizar')
                                ->label('Ordenar paradas automáticamente')
                                ->helperText('Usa la heurística del vecino más cercano con las coordenadas de cada pedido. Desactívalo para respetar el orden en que los seleccionaste.')
                                ->default(true)
                                ->live(),
                            Textarea::make('observaciones')
                                ->label('Observaciones para el repartidor')
                                ->placeholder('Ej: tocar timbre del local, entregar antes de las 13:00')
                                ->maxLength(2000),
                        ]),
                ])->columnSpan(3),

                Group::make([
                    Section::make('Ruta sugerida')
                        ->description('Vista previa · distancia en línea recta')
                        ->schema([
                            Text::make(fn (Get $get): HtmlString => self::vistaPreviaDeParadas((array) $get('pedidos'), (bool) $get('optimizar'))),
                            Text::make(fn (Get $get): HtmlString => self::resumenDeRuta($get('vehiculo_id'), (array) $get('pedidos'), (bool) $get('optimizar'))),
                        ]),
                ])->columnSpan(2),
            ]);
    }

    /**
     * @return array<string, string>
     */
    public static function opcionesDePedidos(?string $fecha, ?Ruta $ruta): array
    {
        $pedidosDeLaRuta = $ruta?->entregas->pluck('pedido_id')->all() ?? [];

        return Pedido::query()
            ->with('cliente')
            ->where(fn ($query) => $query
                ->where(fn ($pendientes) => $pendientes
                    ->where('estado', EstadoPedido::Pendiente)
                    ->when($fecha, fn ($q) => $q->where('fecha_entrega', '<=', $fecha)))
                ->orWhereIn('id', $pedidosDeLaRuta))
            ->orderBy('fecha_entrega')
            ->orderBy('numero')
            ->get()
            ->mapWithKeys(fn (Pedido $pedido): array => [
                $pedido->id => sprintf(
                    '%s · %s · %s · %s kg%s',
                    $pedido->codigo,
                    $pedido->cliente?->nombre,
                    $pedido->direccion_entrega,
                    CapacidadVehiculo::formatearKg($pedido->peso_kg),
                    $fecha !== null && $pedido->fecha_entrega->toDateString() < $fecha ? ' (atrasado)' : '',
                ),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function opcionesDeRepartidores(): array
    {
        return Repartidor::query()
            ->with('vehiculo')
            ->where('estado', '!=', EstadoRepartidor::Inactivo)
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn (Repartidor $repartidor): array => [
                $repartidor->id => sprintf(
                    '%s — %s · %s',
                    $repartidor->nombre,
                    $repartidor->estado->getLabel(),
                    $repartidor->vehiculo?->etiqueta() ?? 'sin vehículo',
                ),
            ])
            ->all();
    }

    private static function vehiculo(?string $vehiculoId): ?Vehiculo
    {
        if ($vehiculoId === null) {
            return null;
        }

        return once(fn (): ?Vehiculo => Vehiculo::query()->find($vehiculoId));
    }

    /**
     * @param  list<string>  $pedidoIds
     * @return Collection<string, Pedido>
     */
    private static function pedidos(array $pedidoIds): Collection
    {
        return once(fn (): Collection => Pedido::query()->with('cliente')->whereKey($pedidoIds)->get()->keyBy('id'));
    }

    /**
     * @param  list<string>  $pedidoIds
     */
    public static function evaluarCapacidad(?string $vehiculoId, array $pedidoIds): ?EvaluacionCapacidad
    {
        if ($vehiculoId === null || $pedidoIds === []) {
            return null;
        }

        $vehiculo = self::vehiculo($vehiculoId);

        if ($vehiculo === null) {
            return null;
        }

        return CapacidadVehiculo::evaluar($vehiculo->capacidad_kg, self::pedidos($pedidoIds)->pluck('peso_kg'));
    }

    /**
     * @param  list<string>  $pedidoIds
     */
    private static function detalleDeCarga(array $pedidoIds): string
    {
        if ($pedidoIds === []) {
            return 'Regla RN-03: la suma del peso de todos los pedidos de la ruta no puede superar la capacidad del vehículo.';
        }

        $pedidos = self::pedidos($pedidoIds)->sortBy('numero');

        return sprintf(
            '%s = %s kg. Si la suma supera la capacidad del vehículo, no se permite guardar.',
            $pedidos->map(fn (Pedido $pedido): string => sprintf('%s (%s kg)', $pedido->codigo, CapacidadVehiculo::formatearKg($pedido->peso_kg)))->implode(' + '),
            CapacidadVehiculo::formatearKg($pedidos->sum('peso_kg')),
        );
    }

    /**
     * @param  list<string>  $pedidoIds
     * @return array{pedidos: Collection<string, Pedido>, orden: list<string>, distancia_km: float|null, distancia_orden_ingresado_km: float|null}|null
     */
    private static function planificar(array $pedidoIds, bool $optimizar): ?array
    {
        if ($pedidoIds === []) {
            return null;
        }

        return once(function () use ($pedidoIds, $optimizar): array {
            $pedidos = self::pedidos($pedidoIds);
            $pedidoIds = array_values(array_filter($pedidoIds, fn (string $id): bool => $pedidos->has($id)));

            return ['pedidos' => $pedidos, ...app(AsignadorRuta::class)->planificar($pedidos, $pedidoIds, $optimizar)];
        });
    }

    /**
     * @param  list<string>  $pedidoIds
     */
    private static function vistaPreviaDeParadas(array $pedidoIds, bool $optimizar): HtmlString
    {
        $plan = self::planificar($pedidoIds, $optimizar);

        if ($plan === null) {
            return new HtmlString('<span style="opacity:.7">Selecciona pedidos para ver el orden sugerido de paradas.</span>');
        }

        $filas = collect($plan['orden'])->values()->map(function (string $id, int $indice) use ($plan): string {
            $pedido = $plan['pedidos'][$id];

            return sprintf(
                '<li style="display:flex;gap:.75rem;align-items:flex-start;padding:.5rem 0;border-bottom:1px solid rgba(127,127,127,.2)">'
                .'<span style="flex:none;width:1.6rem;height:1.6rem;border-radius:999px;background:#2563eb;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem">%d</span>'
                .'<span style="flex:1"><strong>%s</strong> — %s (%s)%s</span><span style="white-space:nowrap">%s kg</span></li>',
                $indice + 1,
                e($pedido->direccion_entrega),
                e($pedido->cliente?->nombre),
                e($pedido->codigo),
                $pedido->latitud === null ? ' <em style="opacity:.6">· sin coordenadas</em>' : '',
                e(CapacidadVehiculo::formatearKg($pedido->peso_kg)),
            );
        })->implode('');

        return new HtmlString('<ol style="list-style:none;margin:0;padding:0">'.$filas.'</ol>');
    }

    /**
     * @param  list<string>  $pedidoIds
     */
    private static function resumenDeRuta(?string $vehiculoId, array $pedidoIds, bool $optimizar): HtmlString
    {
        $vehiculo = self::vehiculo($vehiculoId);
        $plan = self::planificar($pedidoIds, $optimizar);
        $evaluacion = self::evaluarCapacidad($vehiculoId, $pedidoIds);

        $distancia = $plan['distancia_km'] ?? null;
        $ahorro = $distancia !== null && ($plan['distancia_orden_ingresado_km'] ?? null) !== null
            ? $plan['distancia_orden_ingresado_km'] - $distancia
            : null;

        $datos = [
            'Vehículo' => $vehiculo?->etiqueta() ?? '—',
            'Carga' => $evaluacion
                ? CapacidadVehiculo::formatearKg($evaluacion->cargaKg).' / '.CapacidadVehiculo::formatearKg($evaluacion->capacidadKg).' kg'
                : '—',
            'Distancia' => $distancia !== null ? number_format($distancia, 1, ',', '.').' km' : 'Faltan coordenadas',
            'Tiempo estimado' => $distancia !== null
                ? PlanificadorRuta::estimarDuracionMin($distancia, count($pedidoIds), $vehiculo?->tipo).' min'
                : '—',
        ];

        if ($optimizar && $ahorro !== null && $ahorro > 0.05) {
            $datos['Ahorro vs. orden ingresado'] = number_format($ahorro, 1, ',', '.').' km';
        }

        $celdas = collect($datos)->map(fn (string $valor, string $etiqueta): string => sprintf(
            '<div><div style="font-size:.8rem;opacity:.7">%s</div><div style="font-weight:700">%s</div></div>',
            e($etiqueta),
            e($valor),
        ))->implode('');

        return new HtmlString('<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">'.$celdas.'</div>');
    }
}

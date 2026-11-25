@php
    $repartidor = $this->repartidor;
    $vehiculo = $repartidor?->vehiculo;
    $entregas = $this->entregas;
    $zona = config('app.zona_horaria_negocio');
    $abiertas = $entregas->reject(fn ($entrega) => $entrega->estaFinalizada());
    $siguiente = $abiertas->first();
    $cerradas = $entregas->count() - $abiertas->count();
    $cargaABordo = $abiertas->sum(fn ($entrega) => (float) $entrega->pedido->peso_kg);
    $capacidad = (float) ($vehiculo?->capacidad_kg ?? 0);
    $pctCarga = $capacidad > 0 ? min(100, round($cargaABordo / $capacidad * 100)) : 0;
    $pctParadas = $entregas->count() > 0 ? round($cerradas / $entregas->count() * 100) : 0;
    $kg = fn ($valor) => \App\Services\CapacidadVehiculo::formatearKg($valor);
    $urlMapa = $this->urlMapa();
@endphp

<x-filament-panels::page>
    <div style="display:flex;flex-direction:column;gap:1rem;max-width:40rem">
        <x-filament::section>
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem">
                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedTruck" style="width:2.25rem;height:2.25rem;color:var(--primary-500)" />
                <div>
                    <div style="font-weight:700;font-size:1.1rem">
                        {{ $vehiculo ? $vehiculo->nombreTipo().' '.$vehiculo->identificador : 'Sin vehículo asignado' }}
                    </div>
                    @if ($vehiculo)
                        <div style="opacity:.7">Capacidad {{ $kg($capacidad) }} kg</div>
                    @endif
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;font-size:.9rem"><span style="opacity:.75">Carga a bordo</span><strong>{{ $kg($cargaABordo) }} / {{ $kg($capacidad) }} kg</strong></div>
            <div style="height:.4rem;border-radius:999px;background:rgba(127,127,127,.25);margin:.35rem 0 .9rem">
                <div style="height:100%;width:{{ $pctCarga }}%;border-radius:999px;background:var(--primary-500)"></div>
            </div>

            <div style="display:flex;justify-content:space-between;font-size:.9rem"><span style="opacity:.75">Paradas</span><strong>{{ $cerradas }} de {{ $entregas->count() }} cerradas</strong></div>
            <div style="height:.4rem;border-radius:999px;background:rgba(127,127,127,.25);margin-top:.35rem">
                <div style="height:100%;width:{{ $pctParadas }}%;border-radius:999px;background:var(--success-500)"></div>
            </div>
        </x-filament::section>

        <div style="display:flex;justify-content:space-between;align-items:center">
            <h2 style="font-weight:600;opacity:.8">Orden sugerido de paradas</h2>
            @if ($urlMapa)
                <x-filament::link :href="$urlMapa" target="_blank" :icon="\Filament\Support\Icons\Heroicon::OutlinedMap">Abrir en mapa</x-filament::link>
            @endif
        </div>

        @forelse ($entregas as $entrega)
            @php
                $pedido = $entrega->pedido;
                $esSiguiente = $siguiente?->is($entrega);
                $finalizada = $entrega->estaFinalizada();
            @endphp

            <div @style([
                'border-radius:.75rem',
                'outline:2px solid var(--primary-500)' => $esSiguiente,
            ])>
                <x-filament::section>
                    <div style="display:flex;gap:.75rem;align-items:flex-start">
                        <div @style([
                            'flex:none;width:2rem;height:2rem;border-radius:999px;display:flex;align-items:center;justify-content:center;font-weight:700',
                            'background:var(--primary-600);color:#fff' => ! $finalizada,
                            'border:1px solid var(--success-500);color:var(--success-500)' => $finalizada,
                        ])>
                            @if ($finalizada) ✓ @else {{ $entrega->orden_parada ?? '•' }} @endif
                        </div>

                        <div style="flex:1;min-width:0">
                            @if ($esSiguiente)
                                <x-filament::badge color="info" size="sm" style="margin-bottom:.25rem">Siguiente parada</x-filament::badge>
                            @endif
                            <div @style(['font-weight:700', 'text-decoration:line-through;opacity:.6' => $finalizada])>
                                {{ $pedido->direccion_entrega }}{{ $pedido->comuna ? ', '.$pedido->comuna : '' }}
                            </div>
                            <div style="opacity:.7;font-size:.9rem">
                                {{ $pedido->codigo }} · {{ $pedido->cliente?->nombre }} · {{ $pedido->resumenCarga() }}
                            </div>
                            @if ($pedido->referencia || $pedido->observaciones)
                                <div style="font-size:.85rem;margin-top:.25rem">{{ collect([$pedido->referencia, $pedido->observaciones])->filter()->implode(' · ') }}</div>
                            @endif
                            @if ($entrega->ruta?->observaciones && ! $finalizada)
                                <div style="font-size:.85rem;margin-top:.25rem;opacity:.8">Nota: {{ $entrega->ruta->observaciones }}</div>
                            @endif

                            @if ($finalizada)
                                <div style="display:flex;gap:.5rem;align-items:center;margin-top:.5rem;flex-wrap:wrap">
                                    <x-filament::badge :color="$entrega->estado->getColor()">
                                        {{ $entrega->estado->getLabel() }} {{ $entrega->finalizada_at?->timezone($zona)->format('H:i') }}
                                    </x-filament::badge>
                                    @if ($entrega->comprobante)
                                        <x-filament::link :href="route('comprobantes.show', $entrega->comprobante)" target="_blank" size="sm">
                                            Comprobante {{ $entrega->comprobante->codigo }}
                                        </x-filament::link>
                                    @endif
                                </div>
                            @elseif ($esSiguiente)
                                <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.75rem">
                                    {{ ($this->confirmarAction)(['entrega' => $entrega->id]) }}
                                    @if ($entrega->estado === \App\Enums\EstadoEntrega::Asignada)
                                        {{ ($this->enCaminoAction)(['entrega' => $entrega->id]) }}
                                    @endif
                                </div>
                            @else
                                <x-filament::badge :color="$entrega->estado->getColor()" size="sm" style="margin-top:.5rem">{{ $entrega->estado->getLabel() }}</x-filament::badge>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            </div>
        @empty
            <x-filament::section>
                <div style="text-align:center;padding:1.5rem 0;opacity:.75">
                    No tienes entregas asignadas para hoy.
                </div>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>

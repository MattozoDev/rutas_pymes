<x-filament-widgets::widget>
    <x-filament::section :heading="'Equipo hoy'" :description="$repartidores->where('estado', \App\Enums\EstadoRepartidor::EnRuta)->count().' en ruta · '.$repartidores->count().' activos'">
        <x-slot name="afterHeader">
            <x-filament::link :href="$urlFlota" size="sm" :icon="\Filament\Support\Icons\Heroicon::ArrowRight" icon-position="after">
                Flota
            </x-filament::link>
        </x-slot>

        @if ($repartidores->isEmpty())
            <div style="text-align:center;padding:1.5rem 0">
                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedUserGroup" style="width:2rem;height:2rem;margin:0 auto .5rem;opacity:.5" />
                <div style="font-weight:600">Sin repartidores activos</div>
                <div style="font-size:.875rem;opacity:.7;margin-top:.25rem">Agrega tu equipo en Repartidores y flota.</div>
            </div>
        @else
            <ul style="list-style:none;margin:-.25rem 0;padding:0">
                @foreach ($repartidores as $repartidor)
                    @php
                        $enRuta = $repartidor['estado'] === \App\Enums\EstadoRepartidor::EnRuta;
                        $avance = $repartidor['total'] > 0 ? round($repartidor['cerradas'] / $repartidor['total'] * 100) : 0;
                    @endphp
                    <li @style([
                        'padding:.75rem 0',
                        'border-top:1px solid rgba(127,127,127,.18)' => ! $loop->first,
                    ])>
                        <a href="{{ $repartidor['url'] }}" style="display:flex;align-items:center;gap:.75rem;text-decoration:none;color:inherit">
                            <span @style([
                                'flex:none;width:2.5rem;height:2.5rem;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem',
                                'background:var(--primary-500);color:#fff' => $enRuta,
                                'background:rgba(127,127,127,.18)' => ! $enRuta,
                            ])>{{ $repartidor['iniciales'] }}</span>

                            <span style="flex:1;min-width:0">
                                <span style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
                                    <span style="font-weight:600;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $repartidor['nombre'] }}</span>
                                    <x-filament::badge size="sm" style="flex:none" :color="$repartidor['estado']->getColor()">{{ $repartidor['estado']->getLabel() }}</x-filament::badge>
                                </span>
                                <span style="display:block;font-size:.8rem;opacity:.65;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $repartidor['vehiculo'] }}</span>

                                @if ($repartidor['total'] > 0)
                                    <span style="display:flex;align-items:center;gap:.5rem;margin-top:.4rem">
                                        <span style="flex:1;height:.35rem;border-radius:999px;background:rgba(127,127,127,.2);overflow:hidden">
                                            <span style="display:block;height:100%;width:{{ $avance }}%;border-radius:999px;background:var(--success-500)"></span>
                                        </span>
                                        <span style="font-size:.75rem;font-variant-numeric:tabular-nums;opacity:.8">{{ $repartidor['cerradas'] }}/{{ $repartidor['total'] }}</span>
                                    </span>
                                @else
                                    <span style="display:block;font-size:.75rem;opacity:.5;margin-top:.25rem">Sin entregas asignadas hoy</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

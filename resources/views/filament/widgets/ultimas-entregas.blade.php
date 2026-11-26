<x-filament-widgets::widget>
    <x-filament::section heading="Últimas entregas" description="Cada cierre del repartidor genera un comprobante digital">
        <x-slot name="afterHeader">
            <x-filament::link :href="$urlTodos" size="sm" :icon="\Filament\Support\Icons\Heroicon::ArrowRight" icon-position="after">
                Comprobantes
            </x-filament::link>
        </x-slot>

        @if ($entregas->isEmpty())
            <div style="text-align:center;padding:1.5rem 0">
                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedDocumentCheck" style="width:2rem;height:2rem;margin:0 auto .5rem;opacity:.5" />
                <div style="font-weight:600">Aún no hay entregas cerradas</div>
                <div style="font-size:.875rem;opacity:.7;margin-top:.25rem">Cuando un repartidor confirme una entrega, aparecerá aquí con su comprobante.</div>
            </div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(17rem,1fr));gap:.75rem">
                @foreach ($entregas as $entrega)
                    <a href="{{ $entrega['url'] }}" style="display:flex;gap:.75rem;padding:.875rem;border:1px solid rgba(127,127,127,.2);border-radius:.75rem;text-decoration:none;color:inherit">
                        <span @style([
                            'flex:none;width:2.25rem;height:2.25rem;border-radius:999px;display:inline-flex;align-items:center;justify-content:center',
                            'background:color-mix(in oklab, var(--success-500) 18%, transparent);color:var(--success-500)' => $entrega['entregado'],
                            'background:color-mix(in oklab, var(--danger-500) 18%, transparent);color:var(--danger-500)' => ! $entrega['entregado'],
                        ])>
                            <x-filament::icon :icon="$entrega['entregado'] ? \Filament\Support\Icons\Heroicon::OutlinedCheck : \Filament\Support\Icons\Heroicon::OutlinedXMark" style="width:1.1rem;height:1.1rem" />
                        </span>
                        <span style="flex:1;min-width:0">
                            <span style="display:flex;justify-content:space-between;gap:.5rem">
                                <span style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $entrega['cliente'] }}</span>
                                <span style="font-size:.75rem;opacity:.6;white-space:nowrap" title="{{ $entrega['hace'] }}">{{ $entrega['hora'] }}</span>
                            </span>
                            <span style="display:block;font-size:.8rem;opacity:.7;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $entrega['direccion'] }}</span>
                            <span style="display:block;font-size:.8rem;margin-top:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $entrega['detalle'] }}</span>
                            <span style="display:flex;align-items:center;gap:.4rem;margin-top:.4rem;font-size:.75rem;opacity:.7">
                                <span style="font-weight:600">{{ $entrega['codigo'] }}</span>
                                <span>· {{ $entrega['pedido'] }} · {{ $entrega['repartidor'] }}</span>
                                @if ($entrega['enviado'])
                                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedEnvelope" style="width:.9rem;height:.9rem" title="Enviado al cliente" />
                                @endif
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

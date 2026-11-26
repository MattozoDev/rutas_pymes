@props(['mapa', 'altura' => '20rem', 'indicaciones' => false])

@php
    $clave = 'mapa-'.md5(json_encode($mapa).($indicaciones ? '1' : '0'));
    $hayPuntos = ! empty($mapa['paradas']);
@endphp

<div>
    @if ($hayPuntos)
        <div
            wire:key="{{ $clave }}"
            wire:ignore
            x-data="mapaEntregas(@js($mapa), @js((bool) $indicaciones))"
        >
            <div
                x-ref="lienzo"
                style="height: {{ $altura }}; width: 100%; border-radius: .75rem; overflow: hidden; isolation: isolate; border: 1px solid rgba(127,127,127,.25)"
                role="img"
                aria-label="Mapa de entregas"
            ></div>

            <p style="margin-top: .5rem; font-size: .8rem; display: flex; align-items: center; gap: .4rem">
                <span x-show="cargando" style="opacity: .7">Calculando recorrido por calles…</span>
                <span x-show="! cargando" x-text="resumen" :style="error ? 'color: var(--warning-600)' : 'opacity: .8'"></span>
            </p>

            <template x-if="pasos.length > 0">
                <details style="margin-top: .5rem; border: 1px solid rgba(127,127,127,.25); border-radius: .75rem; padding: .5rem .75rem">
                    <summary style="cursor: pointer; font-weight: 600; font-size: .875rem" x-text="'Indicaciones paso a paso (' + pasos.length + ')'"></summary>
                    <ol style="margin: .5rem 0 0; padding: 0; list-style: none; max-height: 18rem; overflow-y: auto">
                        <template x-for="(paso, indice) in pasos" :key="indice">
                            <li :style="'display: flex; gap: .5rem; padding: .4rem 0; font-size: .85rem; border-top: 1px solid rgba(127,127,127,.15)' + (paso.llegada ? '; font-weight: 600' : '')">
                                <span style="flex: none; width: 1.25rem; text-align: center" x-text="paso.icono"></span>
                                <span style="flex: 1" x-text="paso.texto"></span>
                                <span style="flex: none; opacity: .6; font-variant-numeric: tabular-nums" x-text="paso.distancia"></span>
                            </li>
                        </template>
                    </ol>
                </details>
            </template>
        </div>
    @else
        <div style="height: 6rem; display: flex; align-items: center; justify-content: center; gap: .5rem; border: 1px dashed rgba(127,127,127,.35); border-radius: .75rem; font-size: .875rem; opacity: .75">
            <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedMapPin" style="width: 1.25rem; height: 1.25rem" />
            {{ $mapa['sinCoordenadas'] > 0 ? 'Sin coordenadas para mostrar en el mapa.' : 'Selecciona pedidos para ver el mapa.' }}
        </div>
    @endif

    @if ($hayPuntos && $mapa['sinCoordenadas'] > 0)
        <p style="margin-top: .25rem; font-size: .8rem; opacity: .7">
            {{ $mapa['sinCoordenadas'] === 1 ? '1 parada no tiene coordenadas' : $mapa['sinCoordenadas'].' paradas no tienen coordenadas' }} y no aparece en el mapa.
        </p>
    @endif
</div>

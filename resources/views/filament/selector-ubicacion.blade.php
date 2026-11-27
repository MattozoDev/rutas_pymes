<div
    wire:ignore
    x-data="mapaSelector($wire.$entangle(@js($rutaLatitud)), $wire.$entangle(@js($rutaLongitud)))"
>
    <div
        x-ref="lienzo"
        style="height: 16rem; width: 100%; border-radius: .75rem; overflow: hidden; isolation: isolate; border: 1px solid rgba(127,127,127,.25)"
    ></div>
    <p style="margin-top: .4rem; font-size: .8rem; opacity: .7">
        Usa «Ubicar» junto a la dirección, o haz clic en el mapa y arrastra el marcador para ajustar el punto exacto.
    </p>
</div>

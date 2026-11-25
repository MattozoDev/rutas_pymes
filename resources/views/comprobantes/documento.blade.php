@php
    $zona = config('app.zona_horaria_negocio');
    $pedido = $comprobante->pedido;
    $cliente = $pedido?->cliente;
    $vehiculo = $comprobante->entrega?->vehiculo;
    $entregado = $comprobante->resultado === \App\Enums\ResultadoEntrega::Entregado;
    $kg = fn ($valor) => \App\Services\CapacidadVehiculo::formatearKg($valor);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Comprobante {{ $comprobante->codigo }} · {{ $comprobante->empresa?->nombre }}</title>
    <style>
        :root {
            --tinta: #111827; --suave: #6b7280; --borde: #e5e7eb; --fondo: #f3f4f6;
            --azul: #2563eb; --ambar: #f59e0b; --verde: #15803d; --verde-bg: #dcfce7; --rojo: #b91c1c; --rojo-bg: #fee2e2;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: var(--tinta); background: var(--fondo); }
        .barra { max-width: 820px; margin: 24px auto 0; padding: 0 16px; display: flex; flex-wrap: wrap; gap: 8px; }
        .boton { display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; border-radius: 8px; border: 1px solid var(--borde); background: #fff; color: var(--tinta); font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .boton.primario { background: var(--azul); border-color: var(--azul); color: #fff; }
        .hoja { max-width: 820px; margin: 16px auto 32px; background: #fff; border: 1px solid var(--borde); border-radius: 14px; padding: 32px; }
        .cabecera { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; border-bottom: 1px solid var(--borde); padding-bottom: 20px; }
        .marca { display: flex; align-items: center; gap: 8px; color: var(--ambar); font-weight: 800; font-size: 20px; }
        .titulo { margin: 10px 0 0; font-size: 22px; }
        .numero { text-align: right; }
        .numero strong { display: block; font-size: 24px; letter-spacing: .5px; }
        .muted { color: var(--suave); font-size: 13px; }
        .estado { display: inline-block; margin-top: 8px; padding: 4px 10px; border-radius: 999px; font-size: 13px; font-weight: 700; }
        .estado.ok { background: var(--verde-bg); color: var(--verde); }
        .estado.no { background: var(--rojo-bg); color: var(--rojo); }
        .grilla { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 24px 0; }
        .grilla h3 { margin: 0 0 6px; font-size: 11px; letter-spacing: .08em; color: var(--suave); text-transform: uppercase; }
        .grilla p { margin: 2px 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; border: 1px solid var(--borde); border-radius: 10px; overflow: hidden; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--borde); }
        th { background: #f9fafb; font-size: 12px; color: var(--suave); font-weight: 600; }
        tr:last-child td { border-bottom: 0; }
        .evidencia { display: grid; grid-template-columns: 220px 1fr; gap: 20px; margin-top: 24px; align-items: start; }
        .foto { width: 220px; height: 160px; border: 1px dashed var(--borde); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--suave); font-size: 13px; overflow: hidden; background: #fafafa; }
        .foto img { width: 100%; height: 100%; object-fit: cover; }
        .firma { margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--borde); font-size: 14px; }
        .verificacion { margin-top: 28px; padding: 14px 16px; border-radius: 10px; background: #eff6ff; border: 1px solid #bfdbfe; font-size: 13px; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .verificacion code { font-size: 15px; font-weight: 700; letter-spacing: 1px; }
        .verificacion a { color: var(--azul); word-break: break-all; }
        .pie { margin-top: 20px; font-size: 12px; color: var(--suave); text-align: center; }
        @media (max-width: 640px) {
            .hoja { padding: 20px; border-radius: 0; border-left: 0; border-right: 0; }
            .grilla { grid-template-columns: 1fr; }
            .evidencia { grid-template-columns: 1fr; }
            .foto { width: 100%; }
            .numero { text-align: left; }
            table, thead, tbody, tr, th, td { display: block; }
            thead { display: none; }
            td { border: 0; padding: 6px 12px; }
            td::before { content: attr(data-label) ": "; color: var(--suave); }
        }
        @media print {
            body { background: #fff; }
            .barra { display: none; }
            .hoja { border: 0; margin: 0; max-width: none; padding: 0; }
            .verificacion { break-inside: avoid; }
            @page { size: A4; margin: 16mm; }
        }
    </style>
</head>
<body>
    <nav class="barra" aria-label="Acciones del comprobante">
        <button type="button" class="boton primario" onclick="window.print()">Descargar PDF / Imprimir</button>
        <a class="boton" href="{{ $enlaceWhatsapp }}" target="_blank" rel="noopener">Compartir por WhatsApp</a>
        <button type="button" class="boton" id="copiar" data-url="{{ $urlPublica }}">Copiar enlace</button>
        @unless ($esVistaPublica)
            <a class="boton" href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/admin') }}">Volver</a>
        @endunless
    </nav>

    <main class="hoja">
        <header class="cabecera">
            <div>
                <div class="marca">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 3 3 6v15l6-3 6 3 6-3V3l-6 3-6-3Z"/><path d="M9 3v15M15 6v15"/></svg>
                    rutapyme
                </div>
                <h1 class="titulo">Comprobante de entrega</h1>
                <div class="muted">Emitido: {{ $comprobante->emitido_at?->timezone($zona)->format('d/m/Y · H:i') }}</div>
            </div>
            <div class="numero">
                <span class="muted">N°</span>
                <strong>{{ $comprobante->codigo }}</strong>
                <span class="estado {{ $entregado ? 'ok' : 'no' }}">{{ $comprobante->resultado?->getLabel() }}</span>
            </div>
        </header>

        <section class="grilla">
            <div>
                <h3>Emisor</h3>
                <p><strong>{{ $comprobante->empresa?->nombre }}</strong></p>
                @if ($comprobante->empresa?->rut)<p>RUT {{ $comprobante->empresa->rut }}</p>@endif
                <p>Despacho propio</p>
                @if ($comprobante->empresa?->comuna)<p class="muted">{{ $comprobante->empresa->comuna }}</p>@endif
            </div>
            <div>
                <h3>Destinatario</h3>
                <p><strong>{{ $cliente?->nombre }}</strong></p>
                <p>{{ $pedido?->direccion_entrega }}{{ $pedido?->comuna ? ', '.$pedido->comuna : '' }}</p>
                @if ($pedido?->referencia)<p class="muted">{{ $pedido->referencia }}</p>@endif
            </div>
            <div>
                <h3>Detalle</h3>
                <p><strong>Pedido {{ $pedido?->codigo }}</strong></p>
                <p>Fecha programada: {{ $pedido?->fecha_entrega?->format('d/m/Y') }}</p>
                <p class="muted">Repartidor: {{ $comprobante->repartidor?->nombre }}</p>
            </div>
        </section>

        <table>
            <thead>
                <tr><th>Carga</th><th>Descripción</th><th>Vehículo</th><th>Resultado</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td data-label="Carga">{{ $pedido?->bultos }} {{ $pedido?->bultos === 1 ? 'bulto' : 'bultos' }} · {{ $kg($pedido?->peso_kg) }} kg</td>
                    <td data-label="Descripción">{{ $pedido?->descripcion_carga ?: ($pedido?->tamano?->getLabel() ? 'Tamaño '.mb_strtolower($pedido->tamano->getLabel()) : '—') }}</td>
                    <td data-label="Vehículo">{{ $vehiculo ? $vehiculo->nombreTipo().' '.$vehiculo->identificador : '—' }}</td>
                    <td data-label="Resultado"><span class="estado {{ $entregado ? 'ok' : 'no' }}" style="margin:0">{{ $comprobante->resultado?->getLabel() }}</span></td>
                </tr>
            </tbody>
        </table>

        <section class="evidencia">
            <div class="foto">
                @if ($urlEvidencia)
                    <img src="{{ $urlEvidencia }}" alt="Foto de evidencia de la entrega">
                @else
                    Sin foto de evidencia
                @endif
            </div>
            <div>
                @if ($entregado)
                    <p style="margin-top:0">Entrega recibida conforme.</p>
                @else
                    <p style="margin-top:0"><strong>Motivo de no entrega:</strong> {{ $comprobante->motivo_no_entrega ?: 'No informado' }}</p>
                @endif
                @if ($comprobante->observaciones)
                    <p class="muted">{{ $comprobante->observaciones }}</p>
                @endif
                <div class="firma">
                    <div class="muted">Recibió</div>
                    <strong>{{ $comprobante->receptor_nombre ?: '—' }}</strong>
                </div>
                @if ($comprobante->enviado_cliente_at)
                    <p class="muted">Enviado al cliente ({{ $comprobante->enviado_a }}) el {{ $comprobante->enviado_cliente_at->timezone($zona)->format('d/m/Y H:i') }}.</p>
                @endif
            </div>
        </section>

        <section class="verificacion">
            <div>
                <div class="muted">Código de verificación</div>
                <code>{{ $codigoVerificacion }}</code>
            </div>
            <div style="flex:1; min-width: 240px">
                <div class="muted">Verifica este comprobante en línea</div>
                <a href="{{ $urlPublica }}">{{ $urlPublica }}</a>
            </div>
        </section>

        <p class="pie">Documento generado por rutapyme a partir del registro del repartidor. No constituye documento tributario.</p>
    </main>

    <script>
        document.getElementById('copiar')?.addEventListener('click', async (evento) => {
            const boton = evento.currentTarget;
            try {
                await navigator.clipboard.writeText(boton.dataset.url);
                boton.textContent = 'Enlace copiado';
            } catch (error) {
                window.prompt('Copia el enlace', boton.dataset.url);
            }
        });
    </script>
</body>
</html>

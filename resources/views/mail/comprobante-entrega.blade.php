<x-mail::message>
# Comprobante de entrega {{ $comprobante->codigo }}

Hola {{ $comprobante->pedido?->cliente?->nombre }}, **{{ $comprobante->empresa?->nombre }}** registró el resultado de tu pedido **{{ $comprobante->pedido?->codigo }}**.

<x-mail::table>
| | |
|:--|:--|
| Resultado | **{{ $comprobante->resultado?->getLabel() }}** |
| Dirección | {{ $comprobante->pedido?->direccion_entrega }}{{ $comprobante->pedido?->comuna ? ', '.$comprobante->pedido->comuna : '' }} |
@if ($comprobante->receptor_nombre)
| Recibió | {{ $comprobante->receptor_nombre }} |
@endif
@if ($comprobante->motivo_no_entrega)
| Motivo | {{ $comprobante->motivo_no_entrega }} |
@endif
| Repartidor | {{ $comprobante->repartidor?->nombre }} |
| Fecha y hora | {{ $comprobante->emitido_at?->timezone(config('app.zona_horaria_negocio'))->format('d/m/Y · H:i') }} |
</x-mail::table>

<x-mail::button :url="$urlComprobante">
Ver comprobante
</x-mail::button>

Puedes guardar el comprobante como PDF desde el enlace. Si no reconoces esta entrega, responde a este correo.

{{ $comprobante->empresa?->nombre }}
</x-mail::message>

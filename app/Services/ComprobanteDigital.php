<?php

namespace App\Services;

use App\Mail\ComprobanteEntregaMail;
use App\Models\Comprobante;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

final class ComprobanteDigital
{
    /**
     * @var list<string>
     */
    public const RELACIONES = [
        'empresa',
        'pedido.cliente',
        'repartidor',
        'entrega.vehiculo',
    ];

    public function urlPublica(Comprobante $comprobante): string
    {
        return URL::signedRoute('comprobantes.publico', ['comprobante' => $comprobante->id]);
    }

    public function urlEvidencia(Comprobante $comprobante): ?string
    {
        if ($comprobante->evidencia_path === null) {
            return null;
        }

        return URL::signedRoute('comprobantes.evidencia', ['comprobante' => $comprobante->id]);
    }

    public function codigoVerificacion(Comprobante $comprobante): string
    {
        $huella = hash_hmac('sha256', implode('|', [
            $comprobante->id,
            $comprobante->codigo,
            $comprobante->resultado?->value,
            $comprobante->receptor_nombre,
            $comprobante->emitido_at?->toIso8601String(),
        ]), (string) config('app.key'));

        return strtoupper(substr($huella, 0, 4).'-'.substr($huella, 4, 4));
    }

    public function enlaceWhatsapp(Comprobante $comprobante): string
    {
        $telefono = preg_replace('/\D+/', '', (string) $comprobante->pedido?->cliente?->telefono);

        if ($telefono !== '' && strlen($telefono) === 9) {
            $telefono = '56'.$telefono;
        }

        $texto = sprintf(
            'Hola %s, %s ha registrado la entrega de tu pedido %s (%s). Revisa tu comprobante %s aquí: %s',
            $comprobante->pedido?->cliente?->nombre,
            $comprobante->empresa?->nombre,
            $comprobante->pedido?->codigo,
            mb_strtolower((string) $comprobante->resultado?->getLabel()),
            $comprobante->codigo,
            $this->urlPublica($comprobante),
        );

        return 'https://wa.me/'.$telefono.'?text='.rawurlencode($texto);
    }

    public function enviarAlCliente(Comprobante $comprobante, ?string $correo = null): bool
    {
        $correo ??= $comprobante->pedido?->cliente?->email;

        if (blank($correo)) {
            return false;
        }

        Mail::to($correo)->send(new ComprobanteEntregaMail($comprobante, $this->urlPublica($comprobante)));

        $comprobante->forceFill([
            'enviado_cliente_at' => now(),
            'enviado_a' => $correo,
        ])->save();

        return true;
    }
}

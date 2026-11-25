<?php

namespace App\Mail;

use App\Models\Comprobante;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComprobanteEntregaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Comprobante $comprobante,
        public string $urlComprobante,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Comprobante %s · Pedido %s · %s',
                $this->comprobante->codigo,
                $this->comprobante->pedido?->codigo,
                $this->comprobante->resultado?->getLabel(),
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.comprobante-entrega',
        );
    }
}

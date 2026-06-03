<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email de bienvenida que se envía a un cliente nuevo dado de alta DESDE EL
 * PANEL (taquilla / cobro manual) en cuyo caso ni tiene cuenta web ni app.
 *
 * Incluye:
 *  - Credenciales temporales (email + password generada al vuelo)
 *  - Link a /password/reset para que la cambie
 *  - Indicaciones para instalar la app y descargar su carnet/abono con QR
 */
class BienvenidaClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public Customer $customer;
    public string $passwordTemporal;
    public ?string $tipoCompra; // 'abono' | 'entrada' | null

    public function __construct(Customer $customer, string $passwordTemporal, ?string $tipoCompra = null)
    {
        $this->customer        = $customer;
        $this->passwordTemporal= $passwordTemporal;
        $this->tipoCompra      = $tipoCompra;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Bienvenido al Algeciras CF! · Acceso a tu cuenta',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bienvenida-cliente',
            with: [
                'customer'   => $this->customer,
                'password'   => $this->passwordTemporal,
                'tipoCompra' => $this->tipoCompra,
                'loginUrl'   => config('app.url') . '/area-personal',
                'resetUrl'   => config('app.url') . '/password/reset?email=' . urlencode($this->customer->email),
            ],
        );
    }
}

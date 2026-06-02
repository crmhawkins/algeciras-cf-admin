<?php

namespace App\Livewire;

use App\Services\Cart;
use App\Services\CheckoutService;
use App\Services\StripePaymentService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CheckoutForm extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $dni = '';
    public string $address = '';
    public string $city = '';
    public string $province = 'Cádiz';
    public string $postal_code = '';
    public string $country = 'España';

    /** Cupón aplicado en el resumen (lo rellena Alpine vía hidden input). */
    public string $coupon_code = '';

    public ?string $error = null;

    /** Set tras crear el PaymentIntent — disparado a la vista para que Stripe.js termine el flow. */
    public ?string $clientSecret = null;
    public ?string $orderReference = null;
    public ?string $publishableKey = null;

    protected function rules(): array
    {
        return [
            'first_name'  => 'required|string|min:2|max:80',
            'last_name'   => 'required|string|min:2|max:80',
            'email'       => 'required|email|max:160',
            'phone'       => 'nullable|string|max:32',
            'dni'         => 'nullable|string|max:24',
            'address'     => 'required|string|min:5|max:200',
            'city'        => 'required|string|min:2|max:80',
            'province'    => 'nullable|string|max:80',
            'postal_code' => 'required|string|max:12',
            'country'     => 'required|string|max:80',
            'coupon_code' => 'nullable|string|max:40',
        ];
    }

    /**
     * Submit: NO completa el pago. Crea Order(pending) + PaymentIntent.
     * Devuelve el clientSecret a la vista para que Stripe.js termine el cobro
     * con stripe.confirmPayment({...}).
     *
     * Si STRIPE_SECRET no está configurado, cae al flujo simulado heredado
     * para que el checkout siga funcional mientras el club no tenga claves.
     */
    public function submit(CheckoutService $checkout, StripePaymentService $stripe)
    {
        $data = $this->validate();

        try {
            // ¿Stripe operativo?
            $stripeOperativo = (string) config('services.stripe.secret') !== '';

            // El cupón (si llegó) se reenvía al CheckoutService como una clave
            // más del array `$data`. El servicio lo aplica al crear la Order.
            if (!empty($this->coupon_code)) {
                $data['coupon_code'] = strtoupper(trim($this->coupon_code));
            }

            if (!$stripeOperativo) {
                // Fallback simulado (el comportamiento que ya tenía la web).
                $order = $checkout->placeOrderSimulated($data);
                $this->dispatch('cart-updated');
                return redirect()->route('pedido', $order->reference);
            }

            $order  = $checkout->createPendingOrder($data);
            $intent = $stripe->createIntentForOrder($order);

            $this->clientSecret   = $intent->client_secret;
            $this->orderReference = $order->reference;
            $this->publishableKey = (string) config('services.stripe.key');

            // Avisamos al JS que monta el PaymentElement.
            $this->dispatch('stripe:ready', [
                'clientSecret'   => $this->clientSecret,
                'publishableKey' => $this->publishableKey,
                'returnUrl'      => route('pedido', $order->reference),
            ]);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    #[Computed]
    public function items()
    {
        return app(Cart::class)->items();
    }

    #[Computed]
    public function subtotal(): float { return app(Cart::class)->subtotal(); }
    #[Computed]
    public function vat(): float { return app(Cart::class)->vat(); }
    #[Computed]
    public function total(): float { return app(Cart::class)->total(); }

    #[Computed]
    public function stripeOperativo(): bool
    {
        return (string) config('services.stripe.secret') !== '';
    }

    public function render()
    {
        return view('livewire.checkout-form');
    }
}

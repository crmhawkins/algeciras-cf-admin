<?php

namespace App\Livewire;

use App\Services\Cart;
use App\Services\CheckoutService;
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

    public ?string $error = null;

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
        ];
    }

    public function submit(CheckoutService $checkout)
    {
        $data = $this->validate();
        try {
            $order = $checkout->placeOrder($data);
            $this->dispatch('cart-updated');
            return redirect()->route('pedido', $order->reference);
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

    public function render()
    {
        return view('livewire.checkout-form');
    }
}

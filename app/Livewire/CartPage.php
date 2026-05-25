<?php

namespace App\Livewire;

use App\Services\Cart;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Carrito')]
class CartPage extends Component
{
    public function updateQty(string $key, int $qty, Cart $cart): void
    {
        $cart->update($key, $qty);
        $this->dispatch('cart-updated');
    }

    public function remove(string $key, Cart $cart): void
    {
        $cart->remove($key);
        $this->dispatch('cart-updated');
    }

    public function clear(Cart $cart): void
    {
        $cart->clear();
        $this->dispatch('cart-updated');
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
        return view('livewire.cart-page');
    }
}

<?php

namespace App\Livewire;

use App\Services\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    public int $count = 0;

    public function mount(Cart $cart): void
    {
        $this->count = $cart->count();
    }

    #[On('cart-updated')]
    public function refresh(Cart $cart): void
    {
        $this->count = $cart->count();
    }

    public function render()
    {
        return view('livewire.cart-counter');
    }
}

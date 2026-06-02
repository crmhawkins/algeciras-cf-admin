<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\Cart;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AddToCart extends Component
{
    public Product $product;
    public ?int $variantId = null;
    public int $qty = 1;
    public ?string $message = null;

    public function mount(Product $product): void
    {
        $this->product = $product->load('variants');
        // Pre-seleccionar primera variante con stock si tiene
        if ($product->has_variants) {
            $first = $this->product->variants->where('active', true)->where('stock', '>', 0)->first();
            $this->variantId = $first?->id;
        }
    }

    public function add(Cart $cart)
    {
        if ($this->product->has_variants && ! $this->variantId) {
            $this->message = 'Selecciona una talla';
            return null;
        }

        // ABONOS y ENTRADAS no pasan por el carrito de la web —
        // tras pulsar el botón van DIRECTOS al checkout/pago. Decisión de
        // producto: el aficionado eligió SU abono concreto, no debe
        // confundir el flujo con un carro de tienda. Para merch sí mantenemos
        // el carrito porque el cliente puede juntar varios items.
        if (in_array($this->product->type, ['abono', 'entrada'], true)) {
            return $this->redirect(
                route('comprar-directo', ['product' => $this->product->slug, 'qty' => max(1, $this->qty)]),
                navigate: false
            );
        }

        $cart->add($this->product->id, $this->variantId, $this->qty);
        $this->message = 'Añadido al carrito ✓';
        $this->dispatch('cart-updated');
        return null;
    }

    #[Computed]
    public function selectedVariant()
    {
        return $this->variantId
            ? $this->product->variants->firstWhere('id', $this->variantId)
            : null;
    }

    public function render()
    {
        return view('livewire.add-to-cart');
    }
}

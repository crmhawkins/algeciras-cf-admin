<div>
@if ($this->items->isEmpty())
    <div class="container mx-auto px-4 lg:px-8 py-24 text-center">
        <h2 class="font-display text-5xl mb-4">Tu carrito está vacío</h2>
        <p class="text-algeciras-gray mb-8">Aún no has añadido nada. Échale un vistazo a la tienda.</p>
        <a href="{{ route('tienda') }}" class="inline-block px-8 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
            Ir a la tienda →
        </a>
    </div>
@else
    <div class="container mx-auto px-4 lg:px-8 py-16 grid lg:grid-cols-3 gap-10">

        {{-- Lista de items --}}
        <div class="lg:col-span-2 space-y-4">
            <h1 class="font-display text-5xl mb-6">Tu carrito</h1>

            @foreach ($this->items as $item)
                <article wire:key="item-{{ $item->key }}" class="bg-white border-2 border-algeciras-black/10 p-4 flex gap-4 items-center">
                    <a href="{{ route('producto', $item->product->slug) }}" class="w-24 h-24 flex-shrink-0 bg-algeciras-cream">
                        @if ($item->product->image)
                            <img src="{{ asset($item->product->image) }}" alt="{{ $item->product->getTranslation('name','es') }}" class="w-full h-full object-contain">
                        @endif
                    </a>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('producto', $item->product->slug) }}" class="font-display text-xl hover:text-algeciras-red block leading-tight">
                            {{ $item->product->getTranslation('name','es') }}
                        </a>
                        @if ($item->variant)
                            <p class="text-xs font-mono uppercase tracking-widest text-algeciras-gray mt-1">
                                Talla: <strong class="text-algeciras-red">{{ $item->variant->size }}</strong>
                                @if ($item->variant->color) · {{ $item->variant->color }} @endif
                            </p>
                        @endif
                        <p class="text-sm text-algeciras-gray mt-1">{{ number_format($item->unit_price, 2, ',', '.') }}€/unidad</p>
                    </div>

                    {{-- Selector qty --}}
                    <div class="inline-flex items-center border-2 border-algeciras-black">
                        <button type="button" wire:click="updateQty('{{ $item->key }}', {{ $item->qty - 1 }})" class="px-3 py-1 hover:bg-algeciras-black hover:text-white font-display">−</button>
                        <span class="px-3 font-display text-lg min-w-[2rem] text-center">{{ $item->qty }}</span>
                        <button type="button" wire:click="updateQty('{{ $item->key }}', {{ $item->qty + 1 }})" class="px-3 py-1 hover:bg-algeciras-black hover:text-white font-display">+</button>
                    </div>

                    <div class="text-right min-w-[80px]">
                        <div class="font-display text-xl text-algeciras-red">{{ number_format($item->total, 2, ',', '.') }}€</div>
                        <button wire:click="remove('{{ $item->key }}')" class="text-xs text-algeciras-gray hover:text-algeciras-red mt-1 underline">eliminar</button>
                    </div>
                </article>
            @endforeach

            <div class="pt-4">
                <button wire:click="clear" wire:confirm="¿Vaciar todo el carrito?" class="text-sm text-algeciras-gray hover:text-algeciras-red">
                    Vaciar carrito
                </button>
            </div>
        </div>

        {{-- Resumen --}}
        <aside class="lg:sticky lg:top-24 lg:self-start bg-algeciras-cream p-6 border-l-8 border-algeciras-red">
            <h2 class="font-display text-3xl mb-6">Resumen</h2>
            <div class="space-y-2 mb-6 text-algeciras-black/85">
                <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($this->subtotal, 2, ',', '.') }}€</span></div>
                <div class="flex justify-between text-sm text-algeciras-gray"><span>IVA incluido</span><span>{{ number_format($this->vat, 2, ',', '.') }}€</span></div>
                <div class="flex justify-between text-sm text-algeciras-gray"><span>Envío</span><span>Calculado en el siguiente paso</span></div>
            </div>
            <div class="border-t-2 border-algeciras-black pt-4 mb-6 flex justify-between items-baseline">
                <span class="font-display text-2xl uppercase">Total</span>
                <span class="font-display text-4xl text-algeciras-red">{{ number_format($this->total, 2, ',', '.') }}€</span>
            </div>
            <button type="button"
                    class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                Tramitar pedido
            </button>
            <p class="text-xs text-algeciras-gray mt-3 text-center">Stripe checkout se activa en fase 2.</p>
        </aside>
    </div>
@endif
</div>

<div class="space-y-5">

    {{-- Selector de talla --}}
    @if ($product->has_variants && $product->variants->count())
        <div>
            <p class="font-display tracking-widest uppercase text-sm mb-3">Talla</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($product->variants->where('active', true)->sortBy('sort_order') as $v)
                    @php $disabled = $v->stock <= 0; @endphp
                    <button
                        type="button"
                        wire:click="$set('variantId', {{ $v->id }})"
                        @disabled($disabled)
                        class="px-5 py-3 border-2 font-display tracking-wider min-w-[60px]
                               {{ $variantId === $v->id
                                    ? 'border-algeciras-red bg-algeciras-red text-white'
                                    : 'border-algeciras-black hover:bg-algeciras-black hover:text-white' }}
                               {{ $disabled ? 'opacity-30 line-through cursor-not-allowed' : '' }}">
                        {{ $v->size }}
                    </button>
                @endforeach
            </div>
            @if ($this->selectedVariant)
                <p class="text-xs text-algeciras-gray mt-2">
                    {{ $this->selectedVariant->stock }} unidades disponibles en talla {{ $this->selectedVariant->size }}
                </p>
            @endif
        </div>
    @endif

    {{-- Cantidad --}}
    <div>
        <p class="font-display tracking-widest uppercase text-sm mb-3">Cantidad</p>
        <div class="inline-flex items-center border-2 border-algeciras-black">
            <button type="button" wire:click="$set('qty', {{ max(1, $qty - 1) }})" class="px-4 py-2 hover:bg-algeciras-black hover:text-white font-display text-xl">−</button>
            <input type="number" wire:model.live="qty" min="1" max="20"
                   class="w-16 text-center border-x-2 border-algeciras-black py-2 font-display text-xl focus:outline-none">
            <button type="button" wire:click="$set('qty', {{ min(20, $qty + 1) }})" class="px-4 py-2 hover:bg-algeciras-black hover:text-white font-display text-xl">+</button>
        </div>
    </div>

    {{-- Botón añadir --}}
    <div class="flex items-center gap-4 flex-wrap">
        <button
            type="button"
            wire:click="add"
            wire:loading.attr="disabled"
            @disabled(! $product->is_available)
            class="px-10 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white
                   font-display tracking-widest uppercase text-lg shadow-brutal
                   hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition
                   disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="add">
                {{ $product->is_available ? 'Añadir al carrito' : 'Agotado' }}
            </span>
            <span wire:loading wire:target="add">Añadiendo...</span>
        </button>

        @if ($message)
            <span wire:transition class="font-display tracking-widest uppercase text-sm text-algeciras-red">
                {{ $message }}
            </span>
        @endif
    </div>

</div>

<div>
@if ($this->items->isEmpty())
    <div class="container mx-auto px-4 lg:px-8 py-24 text-center">
        <h2 class="font-display text-5xl mb-4">No hay nada que pagar</h2>
        <p class="text-algeciras-gray mb-8">Tu carrito está vacío. Añade productos primero.</p>
        <a href="{{ route('tienda') }}" class="inline-block px-8 py-4 bg-algeciras-red text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
            Ir a la tienda →
        </a>
    </div>
@else
    <form wire:submit="submit" class="container mx-auto px-4 lg:px-8 py-16 grid lg:grid-cols-3 gap-10">

        <div class="lg:col-span-2 space-y-6">
            <a href="{{ route('carrito') }}" class="font-display tracking-widest uppercase text-sm text-algeciras-red hover:underline">← Volver al carrito</a>
            <h1 class="font-display text-5xl">Tramitar pedido</h1>

            @if ($error)
                <div class="bg-algeciras-red text-white p-4 font-display tracking-wider">⚠️ {{ $error }}</div>
            @endif

            <section class="bg-white border-2 border-algeciras-black/10 p-6 space-y-4">
                <h2 class="font-display text-2xl">Datos personales</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    @php
                        $input = 'w-full px-4 py-3 bg-white border-2 border-algeciras-black/10 focus:border-algeciras-red outline-none transition';
                    @endphp
                    <div>
                        <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">Nombre *</label>
                        <input type="text" wire:model="first_name" class="{{ $input }}">
                        @error('first_name') <p class="text-xs text-algeciras-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">Apellidos *</label>
                        <input type="text" wire:model="last_name" class="{{ $input }}">
                        @error('last_name') <p class="text-xs text-algeciras-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">Email *</label>
                        <input type="email" wire:model="email" class="{{ $input }}">
                        @error('email') <p class="text-xs text-algeciras-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">Teléfono</label>
                        <input type="tel" wire:model="phone" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">DNI/NIE</label>
                        <input type="text" wire:model="dni" class="{{ $input }}" placeholder="12345678X">
                        <p class="text-xs text-algeciras-gray mt-1">Necesario para entradas y abonos</p>
                    </div>
                </div>
            </section>

            <section class="bg-white border-2 border-algeciras-black/10 p-6 space-y-4">
                <h2 class="font-display text-2xl">Dirección de envío</h2>
                <div>
                    <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">Dirección *</label>
                    <input type="text" wire:model="address" class="{{ $input }}" placeholder="Calle Real 123, 4ºB">
                    @error('address') <p class="text-xs text-algeciras-red mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">CP *</label>
                        <input type="text" wire:model="postal_code" class="{{ $input }}" placeholder="11201">
                        @error('postal_code') <p class="text-xs text-algeciras-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">Ciudad *</label>
                        <input type="text" wire:model="city" class="{{ $input }}" placeholder="Algeciras">
                        @error('city') <p class="text-xs text-algeciras-red mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">Provincia</label>
                        <input type="text" wire:model="province" class="{{ $input }}">
                    </div>
                </div>
            </section>

            <section class="bg-white border-2 border-algeciras-black/10 p-6 space-y-4">
                <h2 class="font-display text-2xl">Pago seguro</h2>

                @if (! $this->stripeOperativo)
                    <div class="bg-algeciras-cream p-4 border-l-4 border-algeciras-gold text-sm">
                        <strong class="font-display tracking-widest uppercase">Pago simulado</strong> · STRIPE_SECRET no configurado. El pedido se marcará como pagado de forma simulada para test.
                    </div>
                @else
                    {{-- Stripe Payment Element se monta aquí cuando submit() devuelva clientSecret --}}
                    <div id="payment-element" class="min-h-[140px]">
                        @if (! $clientSecret)
                            <p class="text-sm text-algeciras-gray">
                                Rellena los datos y pulsa <em>Confirmar pedido</em> — entonces aparecerá la pasarela segura de Stripe (tarjeta, Apple Pay, Google Pay).
                            </p>
                        @endif
                    </div>
                    <div id="payment-message" class="text-sm text-algeciras-red hidden"></div>
                @endif
            </section>
        </div>

        <aside class="lg:sticky lg:top-24 lg:self-start bg-algeciras-cream p-6 border-l-8 border-algeciras-red">
            <h2 class="font-display text-3xl mb-4">Tu pedido</h2>
            <ul class="space-y-2 mb-4 text-sm">
                @foreach ($this->items as $it)
                    <li class="flex justify-between gap-2">
                        <span>{{ $it->qty }} × {{ $it->product->getTranslation('name','es') }} @if($it->variant)<span class="text-algeciras-gray">({{ $it->variant->size }})</span>@endif</span>
                        <span class="font-display whitespace-nowrap">{{ number_format($it->total, 2, ',', '.') }}€</span>
                    </li>
                @endforeach
            </ul>
            @php
                $cartTotal  = (float) $this->total;            // base (sub + vat) sin gestion
                $gestionFee = \App\Models\Order::calcGestionFee($cartTotal);
                $totalConGestion = round($cartTotal + $gestionFee, 2);
            @endphp
            <div class="border-t-2 border-algeciras-black/20 pt-3 space-y-1 text-sm text-algeciras-black/85">
                <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($this->subtotal, 2, ',', '.') }}€</span></div>
                <div class="flex justify-between text-algeciras-gray"><span>IVA incluido</span><span>{{ number_format($this->vat, 2, ',', '.') }}€</span></div>
                @if ($gestionFee > 0)
                    <div class="flex justify-between">
                        <span class="flex items-center gap-1">Gastos de gestión <span class="text-xs text-algeciras-gray">(5%)</span></span>
                        <span>{{ number_format($gestionFee, 2, ',', '.') }}€</span>
                    </div>
                @endif
            </div>
            <div class="border-t-2 border-algeciras-black pt-3 mt-3 mb-4 flex justify-between items-baseline">
                <span class="font-display text-xl uppercase">Total</span>
                <span class="font-display text-3xl text-algeciras-red">{{ number_format($totalConGestion, 2, ',', '.') }}€</span>
            </div>

            @if ($clientSecret)
                {{-- Botón "Pagar" gestionado por Stripe.js (no Livewire) --}}
                <button type="button" id="pay-now-btn"
                        class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition disabled:opacity-50">
                    <span id="pay-now-label">💳 Pagar {{ number_format($totalConGestion, 2, ',', '.') }}€</span>
                    <span id="pay-now-spinner" class="hidden">Procesando…</span>
                </button>
            @else
                <button type="submit" wire:loading.attr="disabled"
                        class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="submit">Confirmar pedido</span>
                    <span wire:loading wire:target="submit">Procesando...</span>
                </button>
            @endif
        </aside>
    </form>

    @if ($this->stripeOperativo)
        {{-- Stripe.js + montaje del Payment Element cuando el evento stripe:ready dispara --}}
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            (function () {
                let stripe = null;
                let elements = null;

                window.addEventListener('stripe:ready', (e) => {
                    const { clientSecret, publishableKey, returnUrl } = e.detail[0] ?? e.detail;
                    if (!clientSecret || !publishableKey) return;

                    stripe = Stripe(publishableKey);
                    elements = stripe.elements({ clientSecret, locale: 'es' });
                    const payEl = elements.create('payment', { layout: 'tabs' });
                    payEl.mount('#payment-element');

                    document.getElementById('pay-now-btn').addEventListener('click', async () => {
                        const btn = document.getElementById('pay-now-btn');
                        const label = document.getElementById('pay-now-label');
                        const spinner = document.getElementById('pay-now-spinner');
                        const msg = document.getElementById('payment-message');

                        btn.disabled = true;
                        label.classList.add('hidden');
                        spinner.classList.remove('hidden');
                        msg.classList.add('hidden');

                        const { error } = await stripe.confirmPayment({
                            elements,
                            confirmParams: { return_url: returnUrl },
                        });

                        if (error) {
                            msg.textContent = error.message || 'Error con el pago.';
                            msg.classList.remove('hidden');
                            btn.disabled = false;
                            label.classList.remove('hidden');
                            spinner.classList.add('hidden');
                        }
                    });
                });
            })();
        </script>
    @endif
@endif
</div>

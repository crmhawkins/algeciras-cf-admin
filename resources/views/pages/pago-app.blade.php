@extends('layouts.app', ['hideHeader' => true, 'hideFooter' => true])

@section('title', 'Pago seguro - Algeciras CF')

@section('content')
    @php
        $stripeOperativo = (string) config('services.stripe.secret') !== '';
        $stripeKey       = (string) config('services.stripe.key');
    @endphp

    <div class="min-h-screen bg-algeciras-cream flex flex-col">
        {{-- Mini header app: solo escudo + total --}}
        <header class="bg-algeciras-red text-white p-5">
            <div class="flex items-center gap-3">
                <img src="{{ asset('algeciras-shield.png') }}" alt="" class="w-10 h-10">
                <div>
                    <h1 class="font-display text-lg leading-none">Pago seguro</h1>
                    <p class="text-xs opacity-85 mt-1">Pedido {{ $order->reference }}</p>
                </div>
            </div>
        </header>

        <main class="flex-1 p-5 max-w-md mx-auto w-full">
            {{-- Resumen del pedido --}}
            <section class="bg-white border-2 border-algeciras-black/10 p-5 mb-4">
                <h2 class="font-display text-xl mb-3">Tu pedido</h2>
                <ul class="space-y-2 mb-3 text-sm">
                    @foreach ($order->items as $it)
                        <li class="flex justify-between gap-2">
                            <span>{{ $it->qty }} × {{ $it->name }}</span>
                            <span class="font-display whitespace-nowrap">{{ number_format($it->total, 2, ',', '.') }}€</span>
                        </li>
                    @endforeach
                </ul>

                @php
                    $itemsTotal  = $order->items->sum('total');
                    $gestionFee  = (float) ($order->gestion_fee ?? 0);
                    $previewSubtotal = (float) $order->subtotal + (float) $order->vat;
                    $previewProductType = optional($order->items->first())->product_type ?? 'all';
                @endphp

                <div class="border-t border-algeciras-black/10 pt-3 space-y-1 text-sm">
                    <div class="flex justify-between text-algeciras-black/75">
                        <span>Subtotal entradas/abono</span>
                        <span class="font-display whitespace-nowrap">{{ number_format($itemsTotal, 2, ',', '.') }}€</span>
                    </div>

                    {{-- Línea descuento (oculta hasta aplicar cupón) --}}
                    <div id="order-discount-line" class="flex justify-between text-green-600 hidden">
                        <span>Descuento</span>
                        <span id="order-discount-amount" class="font-display whitespace-nowrap">−0,00€</span>
                    </div>

                    @if ($gestionFee > 0)
                        <div class="flex justify-between text-algeciras-black/75">
                            <span class="flex items-center gap-1">
                                Gastos de gestión
                                <span class="text-xs text-algeciras-gray">(5%)</span>
                            </span>
                            <span class="font-display whitespace-nowrap">{{ number_format($gestionFee, 2, ',', '.') }}€</span>
                        </div>
                    @endif
                </div>

                {{-- Caja del cupón de descuento --}}
                <div class="border-t border-algeciras-black/10 pt-3 mt-3" x-data="couponBox()">
                    <label class="font-display tracking-widest uppercase text-xs">Código de descuento</label>
                    <div class="flex gap-2 mt-2">
                        <input x-model="code" type="text"
                               class="flex-1 px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono uppercase text-sm"
                               placeholder="ALGECIRAS25" :disabled="applied">
                        <button type="button" @click="apply" :disabled="loading || applied || !code"
                                class="px-4 py-2 bg-algeciras-black text-white font-display tracking-wider uppercase text-xs hover:bg-algeciras-red transition disabled:opacity-50">
                            <span x-show="!applied">Aplicar</span>
                            <span x-show="applied">✓ Aplicado</span>
                        </button>
                    </div>
                    <p x-show="error" x-text="error" class="text-xs text-algeciras-red mt-1"></p>
                    <p x-show="applied && message" x-text="message" class="text-xs text-green-600 mt-1"></p>
                    <input type="hidden" id="coupon-hidden-input" name="coupon_code" value="">
                </div>

                <div class="border-t-2 border-algeciras-black/20 pt-3 mt-3 flex justify-between items-baseline">
                    <span class="font-display text-base uppercase">Total</span>
                    <span class="font-display text-2xl text-algeciras-red" id="order-total-amount">{{ number_format($order->total, 2, ',', '.') }}€</span>
                </div>
            </section>

            @if (! $stripeOperativo)
                {{-- Sin claves Stripe: mensaje honesto y opción de marcar como simulado --}}
                <section class="bg-algeciras-cream border-l-4 border-algeciras-gold p-4 mb-4 text-sm">
                    <p class="font-bold mb-1">Pasarela en preparación</p>
                    <p>El cobro online estará disponible en cuanto el club active la pasarela. De momento, te enviaremos un email para coordinar el pago manualmente.</p>
                </section>
                <form method="POST" action="{{ route('pago-app.simulado', $order->reference) }}">
                    @csrf
                    <button type="submit"
                            class="w-full px-6 py-4 bg-algeciras-gold text-algeciras-black font-display tracking-widest uppercase shadow-brutal">
                        Confirmar reserva (sin cobro)
                    </button>
                </form>
            @else
                {{-- Stripe Elements montado a través del PI creado al cargar la página --}}
                <section class="bg-white border-2 border-algeciras-black/10 p-5 mb-4">
                    <h2 class="font-display text-xl mb-3">Método de pago</h2>
                    <div id="payment-element" class="min-h-[160px]"></div>
                    <div id="payment-message" class="mt-3 text-sm text-algeciras-red hidden"></div>
                </section>
                <button type="button" id="pay-now-btn"
                        class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal disabled:opacity-50">
                    <span id="pay-now-label">💳 Pagar <span id="pay-now-total">{{ number_format($order->total, 2, ',', '.') }}€</span></span>
                    <span id="pay-now-spinner" class="hidden">Procesando…</span>
                </button>

                <script src="https://js.stripe.com/v3/"></script>
                <script>
                    (async function () {
                        const clientSecret   = @json($clientSecret ?? null);
                        const publishableKey = @json($stripeKey);
                        const returnUrl      = @json(route('pago-app.exito', $order->reference));
                        if (!clientSecret || !publishableKey) return;

                        const stripe = Stripe(publishableKey);
                        const elements = stripe.elements({ clientSecret, locale: 'es' });
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
                    })();
                </script>
            @endif

            <p class="text-xs text-center text-algeciras-black/60 mt-6">
                Tras el pago, vuelve a la app — tu abono aparecerá en "Mi cuenta → Abonos" y recibirás email con el QR.
            </p>
        </main>
    </div>

    @push('scripts')
    <script>
        function couponBox() {
            const ORDER_SUBTOTAL = {{ (float) $order->subtotal + (float) $order->vat }};
            const ORDER_TOTAL    = {{ (float) $order->total }};
            const PRODUCT_TYPE   = @json($previewProductType);
            const CSRF_TOKEN     = document.querySelector('meta[name=csrf-token]')?.content || '';

            return {
                code: '',
                loading: false,
                applied: false,
                error: '',
                message: '',
                async apply() {
                    this.loading = true;
                    this.error = '';
                    this.message = '';
                    try {
                        const resp = await fetch('/api/checkout/coupon/preview', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': CSRF_TOKEN,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                code: this.code.trim().toUpperCase(),
                                subtotal: ORDER_SUBTOTAL,
                                product_type: PRODUCT_TYPE,
                            }),
                        });
                        const data = await resp.json();
                        if (resp.ok && data.valid) {
                            const discount = Number(data.discount_amount) || 0;
                            this.applied = true;
                            this.message = `Cupón aplicado: -${discount.toFixed(2)}€`;

                            // Actualizar línea de descuento
                            const line = document.getElementById('order-discount-line');
                            const amt  = document.getElementById('order-discount-amount');
                            if (line && amt) {
                                line.classList.remove('hidden');
                                amt.textContent = `−${discount.toFixed(2).replace('.', ',')}€`;
                            }

                            // Actualizar total
                            const newTotal = Math.max(0, ORDER_TOTAL - discount);
                            const totalEl  = document.getElementById('order-total-amount');
                            if (totalEl) totalEl.textContent = `${newTotal.toFixed(2).replace('.', ',')}€`;
                            const payTotal = document.getElementById('pay-now-total');
                            if (payTotal) payTotal.textContent = `${newTotal.toFixed(2).replace('.', ',')}€`;

                            // Guardar en hidden input para que se mande en /sync u otro POST
                            const hidden = document.getElementById('coupon-hidden-input');
                            if (hidden) hidden.value = this.code.trim().toUpperCase();
                        } else {
                            this.error = data.message || 'Código no válido';
                        }
                    } catch (e) {
                        this.error = 'Error al validar el cupón';
                    }
                    this.loading = false;
                }
            };
        }
    </script>
    @endpush
@endsection

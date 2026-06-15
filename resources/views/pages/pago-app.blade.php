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
                {{-- Antes: <img src="algeciras-shield.png"> → 404, el asset no existe.
                     El escudo real está en /img/club/escudo-oficial.svg. --}}
                <img src="{{ asset('img/club/escudo-oficial.svg') }}"
                     alt="Algeciras CF"
                     class="w-10 h-10"
                     onerror="this.style.display='none'">
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

            @guest
                {{-- Visitante sin cuenta — antes de mostrarle la pasarela,
                     pedimos los datos legalmente obligatorios. Al enviarlos
                     creamos su cuenta automáticamente, vinculamos la orden
                     y le redirigimos a esta misma URL (ya autenticado),
                     que ya verá Stripe Elements o el botón gratis. --}}
                <section class="bg-white border-2 border-algeciras-black/10 p-5 mb-4">
                    <h2 class="font-display text-xl mb-1">Tus datos</h2>
                    <p class="text-xs text-algeciras-gray mb-4">
                        Necesarios para emitir tu abono y entrada al estadio.
                        Te crearemos cuenta y verás tu compra en "Mi cuenta".
                    </p>

                    @if ($errors->any())
                        <div class="bg-algeciras-red text-white p-3 mb-4 text-sm">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('pago-app.guest-checkout', $order->reference) }}" class="space-y-3">
                        @csrf
                        @php
                            $cls = 'w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none font-mono text-sm';
                        @endphp

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-mono uppercase tracking-wider mb-1">Nombre *</label>
                                <input type="text" name="first_name" required value="{{ old('first_name') }}" class="{{ $cls }}">
                            </div>
                            <div>
                                <label class="block text-xs font-mono uppercase tracking-wider mb-1">Apellidos *</label>
                                <input type="text" name="last_name" required value="{{ old('last_name') }}" class="{{ $cls }}">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider mb-1">Email *</label>
                            <input type="email" name="email" required value="{{ old('email') }}" class="{{ $cls }}">
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-mono uppercase tracking-wider mb-1">Teléfono *</label>
                                <input type="tel" name="phone" required value="{{ old('phone') }}" placeholder="600 000 000" class="{{ $cls }}">
                            </div>
                            <div>
                                <label class="block text-xs font-mono uppercase tracking-wider mb-1">DNI/NIE *</label>
                                <input type="text" name="dni" required value="{{ old('dni') }}" placeholder="12345678X" class="{{ $cls }} uppercase">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider mb-1">Fecha de nacimiento *</label>
                            <input type="date" name="birth_date" required value="{{ old('birth_date') }}"
                                   max="{{ now()->subYears(14)->format('Y-m-d') }}"
                                   min="{{ now()->subYears(110)->format('Y-m-d') }}"
                                   class="{{ $cls }}">
                        </div>

                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider mb-1">Dirección *</label>
                            <input type="text" name="address" required value="{{ old('address') }}" placeholder="Calle Real 123, 4ºB" class="{{ $cls }}">
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-mono uppercase tracking-wider mb-1">CP *</label>
                                <input type="text" name="postal_code" required value="{{ old('postal_code') }}" placeholder="11201" pattern="[0-9]{5}" class="{{ $cls }}">
                            </div>
                            <div>
                                <label class="block text-xs font-mono uppercase tracking-wider mb-1">Ciudad *</label>
                                <input type="text" name="city" required value="{{ old('city', 'Algeciras') }}" class="{{ $cls }}">
                            </div>
                            <div>
                                <label class="block text-xs font-mono uppercase tracking-wider mb-1">Provincia</label>
                                <input type="text" name="province" value="{{ old('province', 'Cádiz') }}" class="{{ $cls }}">
                            </div>
                        </div>

                        <div class="border-t border-algeciras-black/10 pt-3 mt-3">
                            <p class="text-xs text-algeciras-gray mb-3">Te creamos cuenta con esta contraseña para que veas tu abono / entrada en "Mi cuenta":</p>
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-mono uppercase tracking-wider mb-1">Contraseña *</label>
                                    <input type="password" name="password" required minlength="6" class="{{ $cls }}">
                                </div>
                                <div>
                                    <label class="block text-xs font-mono uppercase tracking-wider mb-1">Confirmar *</label>
                                    <input type="password" name="password_confirmation" required minlength="6" class="{{ $cls }}">
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition mt-2">
                            Continuar al pago →
                        </button>
                    </form>

                    <p class="text-xs text-center text-algeciras-gray mt-4">
                        ¿Ya tienes cuenta?
                        <a href="{{ route('area-personal') }}?next={{ urlencode(url()->current()) }}" class="text-algeciras-red hover:underline">Inicia sesión</a>
                    </p>
                </section>
            @else
            @if (! empty($freeOrder))
                {{-- Total < 0.50€ (típicamente cupón 100%). No tiene sentido
                     cobrar a Stripe — confirmamos como pagado simulado.
                     Antes usábamos bg-green-600/50 que Tailwind v4 NO incluía
                     en el bundle (clases no usadas en otro sitio del proyecto)
                     → el botón salía sin color. Cambiado a paleta brand. --}}
                <section class="bg-algeciras-cream border-l-4 border-algeciras-red p-4 mb-4 text-sm">
                    <p class="font-display tracking-widest uppercase mb-1">¡Pedido gratuito!</p>
                    <p>Tu pedido queda confirmado sin cobro. Pulsa el botón para finalizar.</p>
                </section>
                <form method="POST" action="{{ route('pago-app.simulado', $order->reference) }}">
                    @csrf
                    <button type="submit"
                            class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                        ✓ Confirmar pedido (gratis)
                    </button>
                </form>
            @elseif (! $stripeOperativo)
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
            @elseif (($gateway ?? 'redsys') === 'redsys')
                {{-- === REDSYS TPV virtual (Banco Sabadell) ===
                     El usuario debe aceptar las condiciones (checkbox) antes
                     de que se le redirija a la pasarela. Requisito Redsys +
                     Ley 34/2002 LSSI-CE. --}}
                <section class="bg-white border-2 border-algeciras-black/10 p-5 mb-4">
                    <h2 class="font-display text-xl mb-3">Método de pago</h2>
                    <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50">
                        <div class="text-3xl">💳</div>
                        <div class="text-sm leading-relaxed">
                            <strong>Tarjeta de crédito o débito</strong> (Visa, Mastercard, Maestro).<br>
                            Procesado por {{ config('club.banco') }} mediante <strong>Redsys</strong>
                            con autenticación segura 3D Secure.<br>
                            <em class="text-xs text-gray-600">No almacenamos los datos de tu tarjeta.</em>
                        </div>
                    </div>

                    <label class="flex items-start gap-2 mt-5 text-sm cursor-pointer">
                        <input type="checkbox" id="aceptar-condiciones" class="mt-0.5 w-5 h-5 accent-algeciras-red" required>
                        <span>
                            He leído y acepto las
                            <a href="{{ route('condiciones-venta') }}" target="_blank" class="text-algeciras-red underline">
                                condiciones de venta, cancelación y devolución</a>,
                            la <a href="{{ route('politica-entrega') }}" target="_blank" class="text-algeciras-red underline">
                                política de entrega</a> y el
                            <a href="{{ route('aviso-legal') }}" target="_blank" class="text-algeciras-red underline">aviso legal</a>.
                        </span>
                    </label>

                    <p class="mt-3 text-xs text-gray-500 leading-snug">
                        El cobro lo procesa <strong>{{ config('club.comercio.razon_social') }}</strong>
                        (CIF {{ config('club.comercio.cif') }}) mediante TPV Virtual de Banco Sabadell.
                        El cargo en tu tarjeta figurará a nombre de este comercio.
                    </p>
                </section>

                <a href="{{ route('redsys.start', $order->reference) }}"
                   id="pay-now-btn"
                   onclick="if(!document.getElementById('aceptar-condiciones').checked){event.preventDefault();alert('Debes aceptar las condiciones de venta para continuar.');return false;}"
                   class="block text-center w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal opacity-50 pointer-events-none transition"
                   data-disabled="true">
                    💳 Pagar con tarjeta · {{ number_format($order->total, 2, ',', '.') }}€
                </a>

                <script>
                    document.getElementById('aceptar-condiciones').addEventListener('change', function (e) {
                        const btn = document.getElementById('pay-now-btn');
                        if (e.target.checked) {
                            btn.classList.remove('opacity-50', 'pointer-events-none');
                            btn.removeAttribute('data-disabled');
                        } else {
                            btn.classList.add('opacity-50', 'pointer-events-none');
                            btn.setAttribute('data-disabled', 'true');
                        }
                    });
                </script>

            @else
                {{-- Stripe Elements (legacy, sólo activo si PAYMENT_GATEWAY=stripe) --}}
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
            @endguest

            <p class="text-xs text-center text-algeciras-black/60 mt-6">
                Tras el pago, tu abono aparecerá en "Mi cuenta → Abonos" y recibirás email con el QR.
            </p>
        </main>
    </div>

    @push('scripts')
    <script>
        function couponBox() {
            // CAMBIO 2026-06-02: antes este código llamaba a
            // /api/checkout/coupon/preview que sólo VALIDABA el cupón pero
            // NO lo aplicaba a la Order — el descuento sólo se pintaba en
            // UI y Stripe cobraba el total original (BUG CRÍTICO).
            // Ahora llamamos a /coupon/apply que actualiza la Order Y
            // recrea el PaymentIntent. Al éxito recargamos para que
            // Stripe Elements se reinicialice con el nuevo client_secret.
            const ORDER_REF      = @json($order->reference);
            const CSRF_TOKEN     = document.querySelector('meta[name=csrf-token]')?.content || '';

            return {
                code: '',
                loading: false,
                applied: false,
                error: '',
                message: '',
                async apply() {
                    if (!this.code.trim()) {
                        this.error = 'Introduce un código';
                        return;
                    }
                    this.loading = true;
                    this.error = '';
                    this.message = '';
                    try {
                        const resp = await fetch(`/api/checkout/order/${encodeURIComponent(ORDER_REF)}/coupon/apply`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': CSRF_TOKEN,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                code: this.code.trim().toUpperCase(),
                            }),
                        });
                        const data = await resp.json();
                        if (resp.ok && data.success) {
                            this.applied = true;
                            this.message = data.message || 'Cupón aplicado';
                            // Recargamos para que el backend renderice con
                            // los totales actualizados y monte un Stripe
                            // Elements nuevo con el client_secret correcto.
                            setTimeout(() => window.location.reload(), 400);
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

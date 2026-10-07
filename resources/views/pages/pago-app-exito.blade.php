@extends('layouts.app', ['hideHeader' => true, 'hideFooter' => true])

@section('title', 'Pago completado - Algeciras CF')

@section('content')
    <div class="min-h-screen bg-algeciras-cream flex flex-col items-center justify-center p-5 text-center">
        {{-- Antes: algeciras-shield.png (404) → cuadro vacío. El escudo real
             está en /img/club/escudo-oficial.svg. --}}
        <img src="{{ asset('img/club/escudo-oficial.svg') }}"
             alt="Algeciras CF"
             class="w-20 h-20 mb-4"
             onerror="this.style.display='none'">

        @if ($order->status === 'paid')
            <div class="text-6xl mb-2">✅</div>
            <h1 class="font-display text-4xl mb-2">¡Pago completado!</h1>
            <p class="text-algeciras-gray mb-4 max-w-sm">
                Pedido <strong>{{ $order->reference }}</strong><br>
                Total cobrado: {{ number_format($order->total, 2, ',', '.') }}€
            </p>
            <p class="text-sm text-algeciras-gray mb-8 max-w-sm">
                Tu abono ya está disponible en <strong>Mi cuenta → Abonos</strong>
                con el QR de acceso al estadio. Te hemos enviado también el
                resumen por email.
            </p>
        @else
            <div class="text-6xl mb-2">⏳</div>
            <h1 class="font-display text-4xl mb-2">Pago en proceso</h1>
            <p class="text-algeciras-gray mb-4 max-w-sm">
                Estamos confirmando con el banco.
            </p>
            <p class="text-sm text-algeciras-gray mb-8 max-w-sm">
                Recibirás email cuando termine. Tu pedido es <strong>{{ $order->reference }}</strong>.
            </p>
        @endif

        {{-- 2 botones: ver mi compra (web) y volver al inicio (app móvil) --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ url('/area-personal/abonos') }}"
               class="px-6 py-3 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition text-center">
                Ver mi abono →
            </a>
            <a href="{{ url('/') }}"
               class="px-6 py-3 border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white font-display tracking-widest uppercase text-center transition">
                Volver al inicio
            </a>
        </div>

        <p class="text-xs text-algeciras-gray mt-6 max-w-sm">
            Si llegaste desde la app móvil, cierra esta pestaña y vuelve a la app — tu compra ya está aplicada en tu cuenta.
        </p>
    </div>
@endsection

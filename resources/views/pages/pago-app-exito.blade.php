@extends('layouts.app', ['hideHeader' => true, 'hideFooter' => true])

@section('title', 'Pago completado - Algeciras CF')

@section('content')
    <div class="min-h-screen bg-algeciras-cream flex flex-col items-center justify-center p-5 text-center">
        <img src="{{ asset('algeciras-shield.png') }}" alt="" class="w-20 h-20 mb-4">

        @if ($order->status === 'paid')
            <div class="w-20 h-20 mb-4 rounded-full bg-green-100 flex items-center justify-center text-4xl">✅</div>
            <h1 class="font-display text-4xl mb-2">¡Pago completado!</h1>
            <p class="text-algeciras-gray mb-4 max-w-xs">
                Pedido <strong>{{ $order->reference }}</strong><br>
                Total cobrado: {{ number_format($order->total, 2, ',', '.') }}€
            </p>
            <p class="text-sm text-algeciras-gray mb-8 max-w-xs">
                Tu abono se enviará por email con el QR de acceso al estadio.
            </p>
        @else
            <div class="w-20 h-20 mb-4 rounded-full bg-yellow-100 flex items-center justify-center text-4xl">⏳</div>
            <h1 class="font-display text-4xl mb-2">Pago en proceso</h1>
            <p class="text-algeciras-gray mb-4 max-w-xs">
                Estamos confirmando con el banco.
            </p>
            <p class="text-sm text-algeciras-gray mb-8 max-w-xs">
                Recibirás email cuando termine. Tu pedido es <strong>{{ $order->reference }}</strong>.
            </p>
        @endif

        <button onclick="window.close(); setTimeout(()=>window.history.back(), 200)"
                class="px-8 py-3 bg-algeciras-red text-white font-display tracking-widest uppercase shadow-brutal">
            Volver a la app
        </button>

        <p class="text-xs text-algeciras-gray mt-4">
            Si no se cierra automáticamente, vuelve a la app desde tu pantalla principal.
        </p>
    </div>
@endsection

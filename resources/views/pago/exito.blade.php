@extends('layouts.app')

@section('title', 'Pago confirmado · Algeciras CF')

@section('content')
<div class="container mx-auto px-4 py-24 max-w-xl text-center">
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-lg p-8 border border-emerald-300">
        <svg class="mx-auto h-16 w-16 text-emerald-500 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="m9 12 2 2 4-4"/>
        </svg>
        <h1 class="text-2xl font-bold text-emerald-700 mb-2">¡Pago confirmado!</h1>
        <p class="mb-6">Hemos recibido tu pago correctamente. En breve recibirás un email con
            la confirmación y tu carnet/entrada digital.</p>

        @if($order)
            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4 text-left text-sm mb-6">
                <div><strong>Referencia:</strong> {{ $order->reference }}</div>
                <div><strong>Importe:</strong> € {{ number_format((float) $order->total, 2, ',', '.') }}</div>
                <div><strong>Email:</strong> {{ $order->customer?->email ?: $order->guest_email }}</div>
            </div>
            <a href="{{ url('/area-personal') }}" class="inline-block bg-algeciras-red text-white px-6 py-3 rounded-lg font-semibold">
                Ver mi pedido en mi área personal
            </a>
        @else
            <a href="{{ url('/') }}" class="inline-block bg-algeciras-red text-white px-6 py-3 rounded-lg font-semibold">
                Volver a la web
            </a>
        @endif

        <p class="text-xs text-gray-500 mt-6">Si no recibes el email en los próximos 5 minutos,
           revisa tu spam o escribe a {{ config('club.email') }}.</p>
    </div>
</div>
@endsection

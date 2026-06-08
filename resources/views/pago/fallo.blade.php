@extends('layouts.app')

@section('title', 'Pago no completado · Algeciras CF')

@section('content')
<div class="container mx-auto px-4 py-24 max-w-xl text-center">
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-lg p-8 border border-red-300">
        <svg class="mx-auto h-16 w-16 text-red-500 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="m15 9-6 6m0-6 6 6"/>
        </svg>
        <h1 class="text-2xl font-bold text-red-700 mb-2">El pago no se ha completado</h1>
        <p class="mb-6">
            El banco no ha autorizado la operación o la has cancelado. No se ha realizado
            ningún cargo en tu tarjeta.
        </p>

        @if($order)
            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4 text-left text-sm mb-6">
                <div><strong>Referencia:</strong> {{ $order->reference }}</div>
                <div><strong>Importe:</strong> € {{ number_format((float) $order->total, 2, ',', '.') }}</div>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ $retry }}" class="bg-algeciras-red text-white px-6 py-3 rounded-lg font-semibold">
                Reintentar el pago
            </a>
            <a href="{{ url('/') }}" class="text-algeciras-red border border-algeciras-red px-6 py-3 rounded-lg font-semibold">
                Volver a la web
            </a>
        </div>

        <p class="text-xs text-gray-500 mt-6">¿Necesitas ayuda? Escríbenos a
           {{ config('club.email') }} o llama al {{ config('club.telefono') }}.</p>
    </div>
</div>
@endsection

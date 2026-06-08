@extends('layouts.app')

@section('title', 'Pago seguro · Algeciras CF')

@section('content')
<div class="container mx-auto px-4 py-24 max-w-xl text-center">
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-lg p-8 border">
        <div class="mb-4">
            <svg class="mx-auto h-12 w-12 text-algeciras-red animate-spin" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-2">Conectando con tu banco…</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            Te estamos redirigiendo a la pasarela segura del banco para completar el pago.<br>
            Por favor, no cierres esta ventana.
        </p>

        <form id="redsys-form" method="POST" action="{{ $actionUrl }}">
            @foreach($fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <noscript>
                <button type="submit" class="bg-algeciras-red text-white px-6 py-3 rounded-lg">
                    Continuar al pago
                </button>
            </noscript>
        </form>

        <p class="text-xs text-gray-500 mt-6">
            Pago procesado por {{ config('club.banco') }} mediante Redsys con 3D Secure.
            Tu importe es <strong>€ {{ number_format((float) $order->total, 2, ',', '.') }}</strong>.
        </p>
    </div>
</div>

<script>
    // Auto-submit. Algunos navegadores requieren un tick antes del submit
    // para que el form esté completamente parseado.
    setTimeout(function () {
        document.getElementById('redsys-form').submit();
    }, 600);
</script>
@endsection

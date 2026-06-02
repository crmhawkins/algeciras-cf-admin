@extends('layouts.app', ['hideHeader' => true, 'hideFooter' => true])

@section('title', 'Validación de QR - Algeciras CF')

@section('content')
    <div class="min-h-screen bg-algeciras-cream flex flex-col items-center justify-center p-5 text-center">
        <img src="{{ asset('algeciras-shield.png') }}" alt="" class="w-20 h-20 mb-4">

        @if ($result && ($result['valid'] ?? false) && $ticket)
            <div class="w-20 h-20 mb-4 rounded-full bg-green-100 flex items-center justify-center text-4xl">✅</div>
            <h1 class="font-display text-4xl mb-2">QR válido</h1>
            <p class="text-algeciras-gray mb-2 max-w-xs">
                @if ($ticket->customer)
                    <strong>{{ trim(($ticket->customer->first_name ?? '').' '.($ticket->customer->last_name ?? '')) }}</strong><br>
                @elseif ($ticket->holder_name)
                    <strong>{{ $ticket->holder_name }}</strong><br>
                @endif
                @if ($ticket->zone)
                    Zona: {{ $ticket->zone->name }}<br>
                @endif
                Estado: {{ $ticket->status }}
            </p>
            <p class="text-xs text-algeciras-gray mt-6 max-w-xs">
                Esta pantalla solo confirma que el QR está bien firmado.
                El control de acceso se hace con la app oficial.
            </p>
        @else
            <div class="w-20 h-20 mb-4 rounded-full bg-red-100 flex items-center justify-center text-4xl">❌</div>
            <h1 class="font-display text-4xl mb-2">QR no válido</h1>
            <p class="text-algeciras-gray mb-2 max-w-xs">
                Motivo: {{ $result['reason'] ?? 'desconocido' }}
            </p>
            <p class="text-xs text-algeciras-gray mt-6 max-w-xs">
                Si esto es tu entrada y crees que es un error, contacta con taquilla.
            </p>
        @endif
    </div>
@endsection

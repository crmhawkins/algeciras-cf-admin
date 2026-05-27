@extends('pages.area-personal._layout')

@php
    $renderTicket = function ($t) {
        return [
            'productName' => $t->product ? $t->product->getTranslation('name', 'es') : 'Entrada',
            'zoneName'    => $t->zone?->name ?? optional($t->product)->zone?->name ?? '—',
            'matchLabel'  => $t->match
                ? 'Algeciras CF vs '.$t->match->opponent
                : 'Partido pendiente',
            'matchDate'   => optional($t->match?->kickoff_at)->format('d/m/Y H:i'),
        ];
    };
@endphp

@section('panel')

<div class="space-y-8">
    <header>
        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">Mis Entradas</p>
        <h2 class="font-display text-3xl md:text-4xl uppercase leading-tight">Tu acceso al estadio</h2>
    </header>

    {{-- ============================ ACTIVAS ============================ --}}
    <section>
        <div class="flex items-baseline justify-between mb-4">
            <h3 class="font-display text-2xl uppercase">Próximos partidos</h3>
            <span class="font-mono text-xs tracking-widest text-algeciras-gray uppercase">
                {{ $activas->count() }} {{ $activas->count() === 1 ? 'entrada' : 'entradas' }}
            </span>
        </div>

        @if($activas->isEmpty())
            <div class="bg-white border-2 border-algeciras-black/10 p-8 text-center">
                <p class="text-algeciras-gray text-sm mb-4">No tienes entradas para próximos partidos.</p>
                <a href="{{ route('tienda', ['type' => 'entrada']) }}"
                   class="inline-block px-5 py-3 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase text-xs transition">
                    Comprar entradas →
                </a>
            </div>
        @else
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($activas as $t)
                    @php $r = $renderTicket($t); @endphp
                    <article class="bg-white border-2 border-algeciras-red shadow-brutal flex gap-4 p-4 clip-tarjeta">
                        <div class="w-24 h-24 flex-shrink-0 bg-algeciras-cream grid place-items-center border border-algeciras-black/10">
                            @if($t->qr_image_path)
                                <img src="{{ asset($t->qr_image_path) }}" alt="QR" class="w-full h-full object-contain">
                            @else
                                <span class="font-mono text-[9px] text-algeciras-gray text-center break-all p-1">
                                    {{ \Illuminate\Support\Str::limit($t->uuid, 12) }}
                                </span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-mono text-[10px] tracking-widest uppercase text-algeciras-red">Entrada</p>
                            <h4 class="font-display text-lg uppercase leading-tight truncate">{{ $r['matchLabel'] }}</h4>
                            <p class="text-xs text-algeciras-gray mt-1">{{ $r['matchDate'] ?: 'Fecha por confirmar' }}</p>
                            <p class="text-sm mt-2">Zona <strong>{{ $r['zoneName'] }}</strong></p>
                            @if($t->row || $t->seat_number)
                                <p class="text-xs text-algeciras-gray">Fila {{ $t->row ?? '—' }} · Butaca {{ $t->seat_number ?? '—' }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ============================ HISTÓRICO ============================ --}}
    @if($historico->count() > 0)
        <section>
            <div class="flex items-baseline justify-between mb-4">
                <h3 class="font-display text-2xl uppercase">Histórico</h3>
                <span class="font-mono text-xs tracking-widest text-algeciras-gray uppercase">
                    {{ $historico->count() }} partidos
                </span>
            </div>

            <div class="bg-white border-2 border-algeciras-black/10">
                @foreach($historico as $t)
                    @php $r = $renderTicket($t); @endphp
                    <div class="flex items-center gap-4 p-4 border-b border-algeciras-black/5 last:border-b-0">
                        <div class="w-12 h-12 flex-shrink-0 grid place-items-center bg-algeciras-cream text-xl">🏟️</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-display uppercase truncate">{{ $r['matchLabel'] }}</p>
                            <p class="text-xs text-algeciras-gray">{{ $r['matchDate'] ?: '—' }} · Zona {{ $r['zoneName'] }}</p>
                        </div>
                        <span class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray">
                            {{ $t->used_at ? '✓ Usada' : ucfirst($t->status ?? '—') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>

@endsection

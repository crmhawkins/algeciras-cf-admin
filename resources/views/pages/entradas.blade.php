@extends('layouts.app')

@section('title', 'Entradas — Próximos partidos')

@section('content')
<section class="bg-algeciras-black text-white py-20 relative overflow-hidden">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <div class="container mx-auto px-4 lg:px-8 relative z-10">
        <p class="font-mono text-white/80 text-sm tracking-[0.4em] uppercase mb-4">Entradas</p>
        <h1 class="font-display text-5xl md:text-7xl leading-none">Próximos<br>partidos</h1>
        <p class="text-xl mt-6 max-w-2xl">Compra tu entrada para un partido concreto en El Mirador.</p>
    </div>
</section>

<section class="container mx-auto px-4 lg:px-8 py-16">
    <div class="flex items-center gap-3 mb-8 flex-wrap">
        <a href="{{ route('comprar') }}" class="font-display tracking-widest uppercase text-sm text-algeciras-red hover:underline">← Cambiar tipo</a>
    </div>

    @if ($partidosConEntrada->isEmpty())
        <div class="bg-algeciras-cream p-6 border-l-4 border-algeciras-gold">
            <p class="font-display text-2xl mb-2">No hay partidos próximos</p>
            <p class="text-algeciras-gray">El calendario aún no tiene partidos publicados.
            Vuelve más adelante o suscríbete a las novedades del club.</p>
        </div>
    @else
        <div class="space-y-6 max-w-4xl">
            @foreach ($partidosConEntrada as $p)
                @php
                    $match    = $p['match'];
                    $entradas = $p['entradas'];
                @endphp
                <article class="bg-white border-l-8 border-algeciras-red p-6">
                    <div class="flex justify-between items-start flex-wrap gap-3 mb-4">
                        <div>
                            <p class="font-mono uppercase tracking-widest text-xs text-algeciras-gray mb-1">
                                {{ $match->matchday ? 'Jornada '.$match->matchday : ($match->competition ?? 'Amistoso') }}
                            </p>
                            <h3 class="font-display text-3xl">Algeciras CF vs {{ $match->opponent }}</h3>
                            <p class="text-sm text-algeciras-gray mt-1">
                                {{ $match->kickoff_at?->isoFormat('dddd D [de] MMMM, HH:mm') }}h
                                · {{ $match->stadium ?? 'Nuevo Mirador' }}
                            </p>
                        </div>
                    </div>

                    @if ($entradas->isEmpty())
                        <p class="text-algeciras-gray italic">Las entradas para este partido aún no están a la venta.</p>
                    @else
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-4">
                            @foreach ($entradas as $entrada)
                                {{-- Antes saltaba directo al checkout — el cliente
                                     reclama poder elegir butaca antes. Mandamos al
                                     plano del estadio con el product + match. --}}
                                <a href="{{ route('estadio', ['product' => $entrada->slug, 'match' => $match->id]) }}"
                                   class="block bg-algeciras-cream hover:bg-algeciras-red hover:text-white transition p-4 border-l-4 border-algeciras-red group">
                                    <p class="font-display text-lg leading-tight">{{ $entrada->getTranslation('name','es') }}</p>
                                    <p class="text-3xl font-display text-algeciras-red group-hover:text-white mt-2">{{ number_format((float)$entrada->price, 2, ',', '.') }}€</p>
                                    <p class="mt-3 font-display tracking-widest uppercase text-xs border-b border-algeciras-red group-hover:border-white inline-block">Elegir butaca →</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection

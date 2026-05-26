@extends('layouts.app')

@section('title', 'Plantilla 2026-27')
@section('description', 'Plantilla del primer equipo del Algeciras CF para la temporada 2026-27. Conoce a los 24 jugadores.')

@section('content')

{{-- =====================================================
     HERO PLANTILLA — gigante, dramático
     ===================================================== --}}
<section class="relative bg-algeciras-black text-white overflow-hidden h-[60vh] min-h-[500px] flex items-end">
    {{-- Capa de grano + escudo gigante translúcido al fondo --}}
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <img src="{{ asset('img/club/escudo.png') }}" alt="" data-fx="hero-badge"
         class="absolute -right-32 top-1/2 -translate-y-1/2 w-[80vh] max-w-none opacity-[0.08] pointer-events-none">

    {{-- Barra roja diagonal al fondo --}}
    <div data-fx="hero-layer" data-speed="0.3"
         class="absolute -bottom-32 left-0 right-0 h-64 bg-algeciras-red transform -skew-y-3 origin-left opacity-90"></div>

    <div class="relative container mx-auto px-4 lg:px-8 pb-20 z-10" data-fx="hero-text">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-4">Primer equipo · Temporada 2025-26</p>
        <h1 class="font-display text-7xl md:text-9xl lg:text-[14rem] leading-[0.85] tracking-tight">
            Plantilla
        </h1>
        <p class="mt-6 text-lg text-algeciras-bone/80 max-w-xl">
            <strong class="text-white" data-fx="counter" data-value="{{ array_sum(array_map(fn ($c) => $c->count(), $byPos)) }}">0</strong> jugadores defendiendo el escudo del Algeciras CF.
        </p>
    </div>
</section>

@php
    $sections = [
        ['key' => 'portero',        'num' => '01', 'label' => 'Porteros'],
        ['key' => 'defensa',        'num' => '02', 'label' => 'Defensas'],
        ['key' => 'centrocampista', 'num' => '03', 'label' => 'Centrocampistas'],
        ['key' => 'delantero',      'num' => '04', 'label' => 'Delanteros'],
        ['key' => 'tecnico',        'num' => '05', 'label' => 'Cuerpo técnico'],
    ];
@endphp

@foreach ($sections as $section)
    @if ($byPos[$section['key']]->count())
        <section class="container mx-auto px-4 lg:px-8 py-16 lg:py-24 relative">

            {{-- Número decorativo gigante de fondo --}}
            <div class="absolute -top-8 right-0 lg:right-12 pointer-events-none select-none" aria-hidden="true">
                <span class="font-display text-[20rem] lg:text-[28rem] leading-none text-algeciras-red/[0.04]">{{ $section['num'] }}</span>
            </div>

            <div class="relative flex items-end justify-between mb-10 flex-wrap gap-4 z-10">
                <div data-fx="reveal">
                    <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">{{ $section['num'] }} / 05</p>
                    <h2 class="font-display text-5xl md:text-7xl">{{ $section['label'] }}</h2>
                </div>
                <span class="font-display text-2xl text-algeciras-red" data-fx="reveal">
                    {{ $byPos[$section['key']]->count() }} {{ Str::plural('jugador', $byPos[$section['key']]->count()) }}
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 lg:gap-8 relative z-10" data-fx="reveal-stagger">
                @foreach ($byPos[$section['key']] as $p)
                    <article data-fx="tilt"
                             class="group relative bg-algeciras-black text-white overflow-hidden cursor-pointer">

                        {{-- Foto con image-reveal (escala 1.4→1) + zoom hover --}}
                        <div class="aspect-[3/4] bg-gradient-to-br from-algeciras-ash to-algeciras-black overflow-hidden" data-fx="image-reveal">
                            @if ($p->photo)
                                <img src="{{ asset($p->photo) }}"
                                     alt="{{ $p->display_name }}"
                                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <span class="font-display text-9xl text-white/10 group-hover:text-algeciras-red/40 transition">
                                        {{ $p->dorsal ?? '?' }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Overlay gradient + corte diagonal rojo arriba derecha --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-algeciras-black via-algeciras-black/30 to-transparent pointer-events-none"></div>
                        <div class="absolute top-0 right-0 w-20 h-20 bg-algeciras-red transform rotate-45 translate-x-10 -translate-y-10 group-hover:translate-x-6 group-hover:-translate-y-6 transition-transform duration-500"></div>

                        {{-- Dorsal grande arriba izq --}}
                        @if ($p->dorsal)
                            <div class="absolute top-3 left-3 font-display text-5xl lg:text-7xl text-white drop-shadow-lg group-hover:text-algeciras-red transition">
                                {{ $p->dorsal }}
                            </div>
                        @endif

                        {{-- Nombre abajo --}}
                        <div class="absolute bottom-0 left-0 right-0 p-4 lg:p-5">
                            <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-algeciras-red mb-1">{{ $p->position }}</p>
                            <h3 class="font-display text-xl lg:text-2xl leading-tight">{{ $p->display_name }}</h3>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endforeach

@endsection

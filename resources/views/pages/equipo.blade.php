@extends('layouts.app')

@section('title', 'Plantilla 2026-27')
@section('description', 'Plantilla del primer equipo del Algeciras CF para la temporada 2026-27.')

@section('content')
<section class="bg-algeciras-black text-white py-16">
    <div class="container mx-auto px-4 lg:px-8">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">Primer Equipo</p>
        <h1 class="font-display text-6xl md:text-7xl">Plantilla 2026-27</h1>
    </div>
</section>

@php
    $labels = [
        'portero' => 'Porteros',
        'defensa' => 'Defensas',
        'centrocampista' => 'Centrocampistas',
        'delantero' => 'Delanteros',
        'tecnico' => 'Cuerpo técnico',
    ];
@endphp

@foreach ($labels as $pos => $label)
    @if ($byPos[$pos]->count())
        <section class="container mx-auto px-4 lg:px-8 py-12">
            <div class="flex items-end justify-between mb-8">
                <h2 class="font-display text-4xl md:text-5xl">{{ $label }}</h2>
                <span class="font-mono text-algeciras-red text-sm">{{ $byPos[$pos]->count() }} jugadores</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach ($byPos[$pos] as $p)
                    <article class="group bg-algeciras-black text-white relative overflow-hidden hover:bg-algeciras-red transition cursor-pointer">
                        <div class="aspect-[3/4] bg-gradient-to-br from-algeciras-ash to-algeciras-black flex items-center justify-center">
                            @if ($p->photo)
                                <img src="{{ asset($p->photo) }}" alt="{{ $p->display_name }}" class="w-full h-full object-cover">
                            @else
                                <span class="font-display text-9xl text-white/10 group-hover:text-white/30 transition">
                                    {{ $p->dorsal ?? '?' }}
                                </span>
                            @endif
                        </div>
                        <div class="p-4">
                            <div class="flex items-baseline gap-2">
                                @if ($p->dorsal)
                                    <span class="font-display text-3xl text-algeciras-red group-hover:text-white">{{ $p->dorsal }}</span>
                                @endif
                                <span class="font-display text-lg uppercase tracking-wider truncate">{{ $p->display_name }}</span>
                            </div>
                            <p class="text-xs text-white/50 group-hover:text-white/90 uppercase tracking-widest mt-1">{{ ucfirst($p->position) }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endforeach

@if ($byPos['centrocampista']->where('active', true)->count() === 0 || $byPos['delantero']->where('active', true)->count() === 0)
    <div class="container mx-auto px-4 lg:px-8 pb-16">
        <div class="bg-algeciras-cream border-2 border-algeciras-red p-6 text-sm">
            <strong class="font-display tracking-widest uppercase text-algeciras-red">Aviso:</strong>
            La plantilla completa se actualizará cuando el club confirme fichajes 26-27. Los datos actuales provienen del scraping inicial del sitio oficial.
        </div>
    </div>
@endif
@endsection

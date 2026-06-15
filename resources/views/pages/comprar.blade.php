@extends('layouts.app')

@section('title', 'Comprar — Abono o Entrada')

@section('content')
<section class="bg-algeciras-red text-white py-20 relative overflow-hidden">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <div class="container mx-auto px-4 lg:px-8 relative z-10">
        <p class="font-mono text-white/80 text-sm tracking-[0.4em] uppercase mb-4">Comprar</p>
        <h1 class="font-display text-5xl md:text-7xl leading-none">¿Qué quieres<br>comprar?</h1>
        <p class="text-xl mt-6 max-w-2xl">Abono de temporada o entrada puntual de un partido.</p>
    </div>
</section>

<section class="container mx-auto px-4 lg:px-8 py-16">
    <div class="grid md:grid-cols-2 gap-6 max-w-5xl mx-auto">
        <a href="{{ route('abonos') }}"
           class="group block bg-white border-l-8 border-algeciras-red p-10 hover:bg-algeciras-red hover:text-white transition shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none">
            <span class="text-5xl block mb-4">🎟️</span>
            <p class="font-mono uppercase tracking-widest text-xs text-algeciras-red group-hover:text-white mb-2">Temporada 2026-27</p>
            <h3 class="font-display text-4xl mb-3">Abono</h3>
            <p class="text-sm text-algeciras-gray group-hover:text-white/85 mb-6">
                Tu sitio en el Mirador para los 19 partidos de Liga.
                Renovar o nuevo abonado.
            </p>
            <span class="inline-block font-display tracking-widest uppercase text-sm border-b-2 border-algeciras-red group-hover:border-white">Elegir abono →</span>
        </a>

        <a href="{{ route('entradas') }}"
           class="group block bg-white border-l-8 border-algeciras-black p-10 hover:bg-algeciras-black hover:text-white transition shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none">
            <span class="text-5xl block mb-4">🎫</span>
            <p class="font-mono uppercase tracking-widest text-xs text-algeciras-gray group-hover:text-white/70 mb-2">Partido concreto</p>
            <h3 class="font-display text-4xl mb-3">Entrada</h3>
            <p class="text-sm text-algeciras-gray group-hover:text-white/85 mb-6">
                Entrada puntual para un partido concreto del calendario.
            </p>
            <span class="inline-block font-display tracking-widest uppercase text-sm border-b-2 border-algeciras-black group-hover:border-white">Ver próximos partidos →</span>
        </a>
    </div>

    <div class="max-w-3xl mx-auto mt-12 bg-algeciras-cream border-l-4 border-algeciras-gold p-5 text-sm text-algeciras-black/85">
        ℹ️ <strong class="font-display tracking-wider uppercase">Si ya eres abonado</strong>,
        no necesitas comprar entrada para los partidos de Liga — tu abono te da acceso.
    </div>
</section>
@endsection

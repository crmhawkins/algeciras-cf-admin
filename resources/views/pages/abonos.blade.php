@extends('layouts.app')

@section('title', 'Hazte abonado 2026-27')

@section('content')
<section class="bg-algeciras-red text-white py-20 relative overflow-hidden">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <div class="container mx-auto px-4 lg:px-8 relative z-10">
        <p class="font-mono text-white/80 text-sm tracking-[0.4em] uppercase mb-4">Temporada 2026-27</p>
        <h1 class="font-display text-6xl md:text-8xl leading-none">Hazte<br>abonado</h1>
        <p class="text-xl mt-6 max-w-2xl">Tu sitio en el Mirador para los <strong>19 partidos de Primera RFEF</strong>. Renovación 15/JUN, captación nuevos 6/JUL.</p>
    </div>
</section>

<section class="container mx-auto px-4 lg:px-8 py-16">
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach ($abonos as $a)
            <article class="bg-white border-l-8 border-algeciras-red p-6 hover:bg-algeciras-black hover:text-white transition group">
                <div class="flex items-center justify-between mb-3">
                    @if ($a->zone)
                        <span class="px-2 py-1 text-xs font-mono uppercase tracking-widest" style="background-color: {{ $a->zone->color }}; color: white;">{{ $a->zone->name }}</span>
                    @endif
                    @if ($a->socios_only)
                        <span class="text-xs font-mono uppercase tracking-widest text-algeciras-red group-hover:text-algeciras-red-light">Renovación</span>
                    @else
                        <span class="text-xs font-mono uppercase tracking-widest text-algeciras-gray group-hover:text-white/70">Nuevo abonado</span>
                    @endif
                </div>
                <h3 class="font-display text-2xl mb-4 leading-tight">{{ $a->getTranslation('name','es') }}</h3>
                <div class="font-display text-5xl text-algeciras-red group-hover:text-white">{{ number_format((float)$a->price, 0) }}€</div>
                <p class="text-xs text-algeciras-gray group-hover:text-white/60 mt-2">IVA {{ $a->vat_rate }}% incluido</p>
                <a href="{{ route('producto', $a->slug) }}" class="inline-block mt-4 font-display tracking-widest uppercase text-sm border-b-2 border-algeciras-red group-hover:border-white">Más info →</a>
            </article>
        @endforeach
    </div>
</section>

<section class="bg-algeciras-cream py-16">
    <div class="container mx-auto px-4 lg:px-8 max-w-3xl">
        <h2 class="font-display text-4xl mb-6">Calendario de la campaña</h2>
        <ul class="space-y-3 text-algeciras-black/80">
            <li class="flex gap-4"><span class="font-display text-algeciras-red w-32 shrink-0">01-06 JUN</span> Teaser pre-anuncio / lista de espera</li>
            <li class="flex gap-4"><span class="font-display text-algeciras-red w-32 shrink-0">07 JUN</span> Aniversario 117 años · revelación campaña</li>
            <li class="flex gap-4"><span class="font-display text-algeciras-red w-32 shrink-0">15 JUN - 5 JUL</span> Renovación socios (objetivo 80% retención)</li>
            <li class="flex gap-4"><span class="font-display text-algeciras-red w-32 shrink-0">6 JUL - 28 AGO</span> Captación nuevos (objetivo 1.500-2.500)</li>
            <li class="flex gap-4"><span class="font-display text-algeciras-red w-32 shrink-0">20-28 AGO</span> Última oportunidad</li>
        </ul>
    </div>
</section>
@endsection

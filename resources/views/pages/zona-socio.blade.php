@extends('layouts.app')

@section('title', 'Zona Socio — Contenido exclusivo')
@section('description', 'Contenido exclusivo para abonados del Algeciras CF: vídeos, descuentos, sorteos y eventos privados.')

@section('content')

{{-- ================= HERO ================= --}}
<section class="relative bg-algeciras-black text-white overflow-hidden py-20 lg:py-28">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <div data-fx="hero-layer" data-speed="0.4"
         class="absolute -bottom-32 left-0 right-0 h-64 bg-algeciras-red transform -skew-y-3 origin-left opacity-90"></div>

    <div class="relative container mx-auto px-4 lg:px-8 z-10" data-fx="hero-text">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-4">Solo abonados</p>
        <h1 data-fx="title-slide" class="font-display font-black text-6xl md:text-8xl lg:text-[10rem] leading-[0.85] tracking-tight uppercase">
            Zona<br><span class="text-algeciras-red">Socio</span>
        </h1>
        <p class="mt-6 text-lg md:text-xl text-algeciras-bone/80 max-w-2xl">
            Contenido exclusivo para los que ya están dentro. Vídeos, descuentos, eventos privados y sorteos que solo se ven aquí.
        </p>
    </div>
</section>

@if (! $isSocio)

{{-- ================= BLOQUEADO ================= --}}
<section class="bg-algeciras-cream py-16 lg:py-24">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="relative">
            {{-- Cards desenfocadas detrás --}}
            <div class="grid md:grid-cols-3 gap-6 filter blur-sm grayscale opacity-60 pointer-events-none select-none">
                @for ($i = 0; $i < 6; $i++)
                    <div class="bg-white border-2 border-algeciras-black/10 shadow-brutal">
                        <div class="aspect-[4/3] bg-gradient-to-br from-algeciras-red/30 to-algeciras-black"></div>
                        <div class="p-5">
                            <p class="font-mono text-xs uppercase tracking-widest text-algeciras-red mb-2">— — — — —</p>
                            <h3 class="font-display text-2xl leading-tight">Contenido bloqueado</h3>
                            <p class="text-sm text-algeciras-gray mt-2">Lorem ipsum dolor sit amet consectetur. Solo para abonados con cuota al día.</p>
                        </div>
                    </div>
                @endfor
            </div>

            {{-- Overlay CTA --}}
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="bg-algeciras-black text-white p-8 lg:p-12 shadow-brutal border-2 border-algeciras-red max-w-xl text-center" data-fx="reveal">
                    <p class="text-5xl mb-3">🔒</p>
                    <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-2">Acceso restringido</p>
                    <h2 class="font-display text-3xl lg:text-4xl leading-tight mb-3 uppercase">Sólo para socios</h2>
                    <p class="text-algeciras-bone/80 mb-6">
                        Este contenido es exclusivo para abonados del Algeciras CF. Hazte socio y desbloquea vídeos, descuentos, sorteos y eventos privados todo el año.
                    </p>
                    <a href="{{ route('abonos') }}"
                       class="inline-block px-7 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal transition">
                        Hazte abonado →
                    </a>
                    <p class="text-xs text-algeciras-bone/50 mt-4">
                        ¿Ya eres socio? <a href="{{ route('area-personal') }}" class="text-algeciras-red hover:underline">Inicia sesión</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

@else

{{-- ================= DESBLOQUEADO ================= --}}
<section class="bg-algeciras-cream py-16 lg:py-24">
    <div class="container mx-auto px-4 lg:px-8">

        <div class="flex items-end justify-between flex-wrap gap-4 mb-10" data-fx="reveal">
            <div>
                <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-2">Para ti, abonado</p>
                <h2 class="font-display text-4xl lg:text-5xl leading-tight">Lo último, en exclusiva.</h2>
            </div>
            <p class="text-sm text-algeciras-gray font-mono">{{ $contents->count() }} contenido{{ $contents->count() === 1 ? '' : 's' }} disponibles</p>
        </div>

        @if ($contents->count())
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6" data-fx="reveal-stagger">
                @foreach ($contents as $c)
                    @php
                        $catColors = [
                            'video'     => 'bg-algeciras-red',
                            'descuento' => 'bg-algeciras-black',
                            'noticia'   => 'bg-algeciras-red',
                            'evento'    => 'bg-algeciras-black',
                            'sorteo'    => 'bg-algeciras-red',
                        ];
                        $catIcons = [
                            'video'     => '▶',
                            'descuento' => '%',
                            'noticia'   => '📰',
                            'evento'    => '★',
                            'sorteo'    => '🎁',
                        ];
                    @endphp
                    <a href="{{ route('zona-socio.content', $c->slug) }}"
                       class="group block bg-white border-2 border-algeciras-black/10 hover:border-algeciras-red hover:-translate-y-1 transition shadow-brutal">
                        <div class="relative aspect-[4/3] bg-gradient-to-br from-algeciras-red/30 to-algeciras-black overflow-hidden">
                            @if ($c->cover_url)
                                <img src="{{ $c->cover_url }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @endif
                            <span class="absolute top-3 left-3 inline-flex items-center gap-1 px-3 py-1 {{ $catColors[$c->category] ?? 'bg-algeciras-black' }} text-white font-mono text-[10px] tracking-[0.3em] uppercase">
                                <span>{{ $catIcons[$c->category] ?? '•' }}</span>
                                <span>{{ $c->category }}</span>
                            </span>
                        </div>
                        <div class="p-5">
                            <p class="font-mono text-xs uppercase tracking-widest text-algeciras-red mb-2">
                                {{ $c->publish_at?->isoFormat('D MMM YYYY') }}
                            </p>
                            <h3 class="font-display text-xl lg:text-2xl leading-tight mb-2">{{ $c->title }}</h3>
                            @if ($c->excerpt)
                                <p class="text-sm text-algeciras-gray line-clamp-3">{{ $c->excerpt }}</p>
                            @endif
                            <span class="inline-block mt-4 font-display tracking-widest uppercase text-xs text-algeciras-red group-hover:underline">
                                Ver más →
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="bg-white border-2 border-algeciras-red p-8 text-center shadow-brutal">
                <p class="font-display text-2xl mb-3">Aún no hay contenido publicado</p>
                <p class="text-algeciras-gray">Estamos preparando los primeros contenidos exclusivos para socios. Vuelve pronto.</p>
            </div>
        @endif

    </div>
</section>

@endif

@endsection

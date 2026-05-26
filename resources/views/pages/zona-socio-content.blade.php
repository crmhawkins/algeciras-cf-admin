@extends('layouts.app')

@section('title', $content->title . ' — Zona Socio')
@section('description', $content->excerpt ?? 'Contenido exclusivo para socios del Algeciras CF.')

@section('content')

@php
    $catColors = [
        'video'     => 'bg-algeciras-red',
        'descuento' => 'bg-algeciras-black',
        'noticia'   => 'bg-algeciras-red',
        'evento'    => 'bg-algeciras-black',
        'sorteo'    => 'bg-algeciras-red',
    ];
    $catLabels = [
        'video'     => 'Vídeo exclusivo',
        'descuento' => 'Descuento socio',
        'noticia'   => 'Noticia',
        'evento'    => 'Evento privado',
        'sorteo'    => 'Sorteo',
    ];

    // Detectar YouTube y construir URL de embed.
    $youtubeEmbed = null;
    if ($content->category === 'video' && $content->external_url) {
        $url = $content->external_url;
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_\-]{6,})~', $url, $m)) {
            $youtubeEmbed = 'https://www.youtube.com/embed/' . $m[1];
        } elseif (str_contains($url, 'youtube.com/embed/')) {
            $youtubeEmbed = $url;
        }
    }
@endphp

{{-- ================= HERO ================= --}}
<section class="relative bg-algeciras-black text-white overflow-hidden">
    @if ($content->cover_url)
        <div class="absolute inset-0">
            <img src="{{ $content->cover_url }}" alt="" class="w-full h-full object-cover opacity-30">
            <div class="absolute inset-0 bg-gradient-to-t from-algeciras-black via-algeciras-black/70 to-algeciras-black/40"></div>
        </div>
    @endif
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>

    <div class="relative container mx-auto px-4 lg:px-8 py-16 lg:py-24 z-10" data-fx="hero-text">
        <a href="{{ route('zona-socio') }}" class="font-display tracking-widest uppercase text-sm text-algeciras-red hover:underline">← Zona Socio</a>

        <span class="inline-flex items-center gap-2 mt-6 px-3 py-1 {{ $catColors[$content->category] ?? 'bg-algeciras-red' }} text-white font-mono text-[10px] tracking-[0.3em] uppercase">
            {{ $catLabels[$content->category] ?? $content->category }}
        </span>

        <h1 data-fx="title-slide" class="font-display font-black text-4xl md:text-6xl lg:text-7xl leading-[0.9] tracking-tight mt-4 uppercase">
            {{ $content->title }}
        </h1>

        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mt-6">
            {{ $content->publish_at?->isoFormat('D MMMM YYYY') }}
        </p>

        @if ($content->excerpt)
            <p class="mt-4 text-lg md:text-xl text-algeciras-bone/85 max-w-3xl">{{ $content->excerpt }}</p>
        @endif
    </div>
</section>

{{-- ================= CONTENIDO ================= --}}
<section class="bg-algeciras-cream py-16 lg:py-20">
    <div class="container mx-auto px-4 lg:px-8 max-w-4xl">

        {{-- Bloque distintivo según categoría --}}
        @switch($content->category)

            @case('video')
                <div class="mb-10 bg-algeciras-black shadow-brutal" data-fx="reveal">
                    @if ($youtubeEmbed)
                        <div class="relative w-full" style="padding-bottom:56.25%;">
                            <iframe src="{{ $youtubeEmbed }}"
                                    title="{{ $content->title }}"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    class="absolute inset-0 w-full h-full"></iframe>
                        </div>
                    @elseif ($content->external_url)
                        <div class="p-10 text-center text-white">
                            <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-3">Vídeo exclusivo</p>
                            <a href="{{ $content->external_url }}" target="_blank" rel="noopener"
                               class="inline-block px-7 py-4 bg-algeciras-red hover:bg-algeciras-red-dark font-display tracking-widest uppercase shadow-brutal transition">
                                Ver vídeo →
                            </a>
                        </div>
                    @endif
                </div>
                @break

            @case('descuento')
                <div class="mb-10 bg-algeciras-red text-white p-8 lg:p-10 shadow-brutal border-2 border-algeciras-black"
                     data-fx="reveal"
                     x-data="{ copied: false, code: '{{ $content->discount_code }}' }">
                    <p class="font-mono text-white/80 text-xs tracking-[0.4em] uppercase mb-2">Tu código de socio</p>
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="font-display font-black text-5xl md:text-6xl tracking-wider">
                            {{ $content->discount_code ?? 'SOCIO' }}
                        </div>
                        <button type="button"
                                @click="navigator.clipboard.writeText(code); copied = true; setTimeout(() => copied = false, 2000)"
                                class="px-5 py-3 bg-algeciras-black hover:bg-algeciras-black/80 text-white font-display tracking-widest uppercase text-sm shadow-brutal transition">
                            <span x-show="!copied">Copiar código</span>
                            <span x-show="copied" x-cloak>✓ Copiado</span>
                        </button>
                    </div>
                    <p class="mt-5 text-white/85">Aplicable en la tienda oficial al finalizar tu pedido.</p>
                    <a href="{{ route('tienda') }}"
                       class="inline-block mt-4 px-6 py-3 bg-white text-algeciras-red font-display tracking-widest uppercase text-sm shadow-brutal hover:translate-y-[-2px] transition">
                        Ir a la tienda →
                    </a>
                </div>
                @break

            @case('evento')
                <div class="mb-10 bg-algeciras-black text-white p-8 lg:p-10 shadow-brutal" data-fx="reveal">
                    <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-2">Evento privado socios</p>
                    <p class="text-algeciras-bone/85 mb-5">Plazas limitadas. Reserva tu sitio antes de que se agoten.</p>
                    <button type="button"
                            onclick="alert('Función de inscripción próximamente disponible.');"
                            class="inline-block px-7 py-4 bg-algeciras-red hover:bg-algeciras-red-dark font-display tracking-widest uppercase shadow-brutal transition">
                        Apuntarme →
                    </button>
                </div>
                @break

            @case('sorteo')
                <div class="mb-10 bg-algeciras-black text-white p-8 lg:p-10 shadow-brutal border-2 border-algeciras-red" data-fx="reveal">
                    <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-2">Sorteo solo socios</p>
                    <p class="text-algeciras-bone/85 mb-5">Una participación por socio. El ganador se anunciará por los canales oficiales del club.</p>
                    <button type="button"
                            onclick="alert('Función de participación próximamente disponible.');"
                            class="inline-block px-7 py-4 bg-algeciras-red hover:bg-algeciras-red-dark font-display tracking-widest uppercase shadow-brutal transition">
                        Participar →
                    </button>
                </div>
                @break

        @endswitch

        {{-- Cover image si no es vídeo (ya está en hero, pero la mostramos también centrada para descuento/noticia) --}}
        @if ($content->cover_image && $content->category !== 'video')
            <div class="mb-10" data-fx="reveal">
                <img src="{{ $content->cover_url }}" alt="" class="w-full max-w-2xl mx-auto shadow-brutal">
            </div>
        @endif

        {{-- Body --}}
        @if ($content->body)
            <div class="prose max-w-none text-algeciras-black/85 text-lg leading-relaxed" data-fx="reveal">
                {!! nl2br(e($content->body)) !!}
            </div>
        @endif

        {{-- Footer del artículo --}}
        <div class="mt-12 pt-8 border-t-2 border-algeciras-black/10 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('zona-socio') }}" class="font-display tracking-widest uppercase text-sm text-algeciras-red hover:underline">
                ← Ver más contenido exclusivo
            </a>
            <p class="font-mono text-xs text-algeciras-gray uppercase tracking-widest">Exclusivo socios · Algeciras CF</p>
        </div>

    </div>
</section>

@endsection

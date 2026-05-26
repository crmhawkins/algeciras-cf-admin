@extends('layouts.app')

@section('title', 'Plano Estadio Nuevo Mirador')
@section('description', 'Selecciona tu sector en el plano interactivo del Estadio Nuevo Mirador del Algeciras CF.')

@push('head')
<style>
    /* Estilos del SVG plano del estadio */
    #plano-estadio { width: 100%; height: auto; max-height: 80vh; display: block; }
    #plano-estadio .recinto-zona {
        cursor: pointer;
        transition: filter 0.2s, opacity 0.2s, transform 0.2s;
        transform-origin: center;
        transform-box: fill-box;
    }
    #plano-estadio .recinto-zona:hover:not(.no-disponible) {
        filter: brightness(1.25) drop-shadow(0 0 8px rgba(207,46,46,0.8));
    }
    #plano-estadio .recinto-zona.no-disponible {
        opacity: 0.35;
        cursor: not-allowed;
    }
    #plano-estadio .recinto-zona.selected polygon,
    #plano-estadio .recinto-zona.selected rect,
    #plano-estadio .recinto-zona.selected path {
        fill: #CF2E2E !important;
        stroke: #ffffff;
        stroke-width: 2;
    }
    #plano-estadio g[aria-describedby] { outline: none; }
    /* Tooltip custom */
    .stadium-tooltip {
        position: fixed;
        z-index: 100;
        pointer-events: none;
        background: #0A0A0A;
        color: white;
        padding: 14px 20px;
        border-left: 4px solid #CF2E2E;
        font-family: 'Bebas Neue', sans-serif;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        font-size: 14px;
        line-height: 1.4;
        opacity: 0;
        transition: opacity 0.15s;
        white-space: nowrap;
        box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    }
    .stadium-tooltip.visible { opacity: 1; }
    .stadium-tooltip .price { color: #CF2E2E; font-size: 20px; }
    .stadium-tooltip .libres { color: #9CA3AF; font-size: 11px; letter-spacing: 0.3em; }
</style>
@endpush

@section('content')

{{-- HERO --}}
<section class="relative bg-algeciras-black text-white overflow-hidden py-16 lg:py-20">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <div data-fx="hero-layer" data-speed="0.4"
         class="absolute -bottom-32 left-0 right-0 h-64 bg-algeciras-red transform -skew-y-3 origin-left opacity-90"></div>

    <div class="relative container mx-auto px-4 lg:px-8 z-10" data-fx="hero-text">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-4">Nuevo Mirador · Plano interactivo</p>
        <h1 class="font-display text-6xl md:text-8xl lg:text-[10rem] leading-[0.85] tracking-tight">Elige tu sitio</h1>
        <p class="mt-6 text-lg text-algeciras-bone/80 max-w-2xl">
            Haz clic en cualquier sector disponible del estadio para añadirlo a tu carrito.
            <strong class="text-algeciras-red">{{ $sectors->where('available', true)->count() }}</strong> sectores abiertos a abonados 2026-27.
        </p>
    </div>
</section>

{{-- LEYENDA + ESTADIO --}}
<section class="container mx-auto px-4 lg:px-8 py-12">

    {{-- Leyenda --}}
    <div class="flex flex-wrap gap-3 mb-8 justify-center" data-fx="reveal-stagger">
        @foreach (['tribuna_alta' => 'Tribuna Alta · 120€', 'tribuna_baja' => 'Tribuna Baja · 120€', 'preferente' => 'Preferente · 75€', 'fondo_norte' => 'Fondo Norte · 60€', 'fondo_sur' => 'Fondo Sur · 60€'] as $z => $label)
            @php
                $color = match($z) {
                    'tribuna_baja', 'tribuna_alta' => '#CF2E2E',
                    'preferente'                   => '#D4A24C',
                    'fondo_norte'                  => '#0A0A0A',
                    'fondo_sur'                    => '#1A1A1A',
                };
            @endphp
            <div class="flex items-center gap-2 px-4 py-2 bg-white border-2 border-algeciras-black/10 font-display tracking-widest uppercase text-sm">
                <span class="inline-block w-4 h-4" style="background-color: {{ $color }}"></span>
                {{ $label }}
            </div>
        @endforeach
    </div>

    {{-- Plano SVG --}}
    <div id="plano-wrapper" class="bg-algeciras-cream border-2 border-algeciras-black/10 p-4 lg:p-8 relative" data-fx="reveal">
        @if ($svg)
            {!! $svg !!}
        @else
            <p class="text-center text-algeciras-gray py-20">Plano no disponible</p>
        @endif
    </div>

    {{-- Tooltip custom global --}}
    <div id="stadium-tooltip" class="stadium-tooltip"></div>

    {{-- Modal selección --}}
    <div x-data="{ open: false, sector: null, qty: 1, tipo: 'adulto' }"
         x-on:open-sector.window="
            sector = $event.detail;
            qty = 1;
            tipo = 'adulto';
            open = true;
         "
         x-show="open"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-[80] bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">

        <div @click.outside="open = false" x-show="open" x-transition class="bg-white max-w-md w-full border-l-8 border-algeciras-red p-6 lg:p-8 relative">
            <button @click="open = false" class="absolute top-3 right-3 text-2xl text-algeciras-gray hover:text-algeciras-red">×</button>

            <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-2" x-text="sector?.zone_label"></p>
            <h2 class="font-display text-3xl md:text-4xl mb-4" x-text="sector?.name"></h2>

            <p class="text-sm text-algeciras-gray mb-6">
                <strong x-text="sector?.capacity"></strong> plazas disponibles
            </p>

            <div class="mb-5">
                <p class="font-display tracking-widest uppercase text-sm mb-2">Tipo</p>
                <div class="flex gap-2">
                    <button type="button"
                            @click="tipo = 'adulto'"
                            :class="tipo === 'adulto' ? 'bg-algeciras-red text-white' : 'border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white'"
                            class="flex-1 px-4 py-3 font-display tracking-wider">
                        <span x-text="'Adulto ' + (sector?.price_adult ? parseFloat(sector.price_adult).toFixed(2).replace('.',',') + '€' : '')"></span>
                    </button>
                    <button type="button"
                            @click="tipo = 'youth'"
                            :class="tipo === 'youth' ? 'bg-algeciras-red text-white' : 'border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white'"
                            class="flex-1 px-4 py-3 font-display tracking-wider">
                        <span x-text="'Infantil ' + (sector?.price_youth ? parseFloat(sector.price_youth).toFixed(2).replace('.',',') + '€' : '')"></span>
                    </button>
                </div>
            </div>

            <div class="mb-6">
                <p class="font-display tracking-widest uppercase text-sm mb-2">Cantidad</p>
                <div class="inline-flex items-center border-2 border-algeciras-black">
                    <button type="button" @click="qty = Math.max(1, qty - 1)" class="px-4 py-2 hover:bg-algeciras-black hover:text-white font-display text-xl">−</button>
                    <span x-text="qty" class="w-16 text-center font-display text-2xl"></span>
                    <button type="button" @click="qty = Math.min(8, qty + 1)" class="px-4 py-2 hover:bg-algeciras-black hover:text-white font-display text-xl">+</button>
                </div>
            </div>

            <div class="border-t-2 border-algeciras-black pt-4 mb-5 flex justify-between items-baseline">
                <span class="font-display text-xl uppercase tracking-widest">Total</span>
                <span class="font-display text-3xl text-algeciras-red"
                      x-text="((tipo === 'youth' ? parseFloat(sector?.price_youth || 0) : parseFloat(sector?.price_adult || 0)) * qty).toFixed(2).replace('.',',') + '€'"></span>
            </div>

            <button type="button"
                    @click="
                        alert('Sector ' + sector.name + ' x' + qty + ' añadido. (Próximamente integración real con carrito Livewire vía POST.)');
                        open = false;
                    "
                    class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                Añadir al carrito
            </button>
        </div>
    </div>
</section>

{{-- =====================================================
     LISTA ALTERNATIVA POR ZONA (accesibilidad / mobile)
     ===================================================== --}}
<section class="bg-algeciras-cream py-16">
    <div class="container mx-auto px-4 lg:px-8">
        <h2 class="font-display text-5xl mb-8 text-center" data-fx="title-slide">O elige por zona</h2>
        @php
            $zoneLabels = [
                'tribuna_alta' => 'Tribuna Alta',
                'tribuna_baja' => 'Tribuna Baja',
                'preferente'   => 'Preferente',
                'fondo_norte'  => 'Fondo Norte',
                'fondo_sur'    => 'Fondo Sur',
            ];
            $zoneColors = [
                'tribuna_baja' => '#CF2E2E',
                'tribuna_alta' => '#CF2E2E',
                'preferente'   => '#D4A24C',
                'fondo_norte'  => '#0A0A0A',
                'fondo_sur'    => '#1A1A1A',
            ];
        @endphp
        @foreach ($zoneLabels as $zKey => $zLabel)
            @if ($byZone->has($zKey))
                <div class="mb-10" data-fx="reveal">
                    <h3 class="font-display text-3xl mb-4 flex items-center gap-3">
                        <span class="inline-block w-6 h-6" style="background-color: {{ $zoneColors[$zKey] ?? '#999' }}"></span>
                        {{ $zLabel }}
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        @foreach ($byZone->get($zKey)->sortBy('parity')->sortBy('number')->where('available', true) as $s)
                            @php
                                $sectorData = [
                                    'id'          => $s->id,
                                    'svg_region'  => $s->svg_region,
                                    'name'        => $s->name,
                                    'zone_label'  => $s->zone_label,
                                    'capacity'    => $s->capacity,
                                    'price_adult' => $s->price_adult,
                                    'price_youth' => $s->price_youth,
                                ];
                            @endphp
                            <button type="button"
                                    data-sector='@json($sectorData)'
                                    onclick="window.dispatchEvent(new CustomEvent('open-sector', {detail: JSON.parse(this.dataset.sector)}))"
                                    class="bg-white p-3 hover:bg-algeciras-red hover:text-white transition border-2 border-algeciras-black/10 text-left group">
                                <p class="font-display text-sm uppercase tracking-wider">{{ $s->name }}</p>
                                <p class="text-xs text-algeciras-gray group-hover:text-white/80 mt-1">
                                    {{ number_format((float)$s->price_adult, 0) }}€ · {{ $s->capacity }} libres
                                </p>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</section>

@php
    $sectorsForJs = $sectors->mapWithKeys(function ($s) {
        return [$s->svg_region => [
            'id'          => $s->id,
            'svg_region'  => $s->svg_region,
            'name'        => $s->name,
            'zone'        => $s->zone,
            'zone_label'  => $s->zone_label,
            'capacity'    => $s->capacity,
            'price_adult' => $s->price_adult,
            'price_youth' => $s->price_youth,
            'available'   => $s->available,
            'color'       => $s->color_hex,
        ]];
    });
@endphp
@push('scripts')
<script>
(function() {
    'use strict';
    // Mapping data-region → datos del sector (rendered desde BD)
    const SECTORS = @json($sectorsForJs);

    const tooltip = document.getElementById('stadium-tooltip');
    const svg = document.querySelector('#plano-wrapper svg');
    if (!svg) return;

    // Asignar id al SVG raíz para nuestros estilos
    svg.id = 'plano-estadio';

    // Recorrer todos los <g data-region> y configurarlos
    svg.querySelectorAll('[data-region]').forEach((el) => {
        const region = el.dataset.region;
        const sector = SECTORS[region];
        if (!sector) return;

        // Recolorear con el color de la zona
        if (sector.available && sector.color) {
            el.querySelectorAll('polygon, rect, path').forEach((shape) => {
                shape.setAttribute('fill', sector.color);
                // Garantizar que los clicks no se traguen los popovers Bootstrap-Vue
                shape.style.pointerEvents = 'auto';
            });
            el.style.cursor = 'pointer';
        } else if (!sector.available) {
            el.classList.add('no-disponible');
        }

        // Hover → mostrar tooltip custom
        el.addEventListener('mouseenter', () => {
            if (!sector.available) {
                tooltip.innerHTML = '<div>' + sector.name + '</div><div class="libres">No disponible</div>';
            } else {
                tooltip.innerHTML =
                    '<div>' + sector.name + '</div>' +
                    '<div class="libres">' + sector.capacity + ' plazas libres</div>' +
                    '<div class="price">' + parseFloat(sector.price_adult).toFixed(2).replace('.', ',') + '€</div>';
            }
            tooltip.classList.add('visible');
        });

        el.addEventListener('mousemove', (e) => {
            tooltip.style.left = (e.clientX + 15) + 'px';
            tooltip.style.top  = (e.clientY + 15) + 'px';
        });

        el.addEventListener('mouseleave', () => {
            tooltip.classList.remove('visible');
        });
    });

    // CLICK DELEGADO (al SVG, no a cada <g>) — soluciona el caso en que
    // el click cae en un <polygon>/<rect>/<path> hijo y no burbujea al <g>.
    svg.addEventListener('click', (e) => {
        const target = e.target.closest('[data-region]');
        if (!target) return;
        const sector = SECTORS[target.dataset.region];
        if (!sector || !sector.available) return;
        // Navegar al detalle de butacas
        window.location.href = '/estadio/sector/' + sector.svg_region;
    }, true); // capture: true por si algún tooltip de Bootstrap-Vue intercepta

    // Bonus: tap táctil en móvil → tras 1er tap muestra tooltip, 2º tap navega.
    // Implementación simple: si es touch device, primer tap previene navegación.
    if ('ontouchstart' in window) {
        let lastTouchedRegion = null;
        svg.addEventListener('touchend', (e) => {
            const target = e.target.closest('[data-region]');
            if (!target) return;
            const region = target.dataset.region;
            const sector = SECTORS[region];
            if (!sector || !sector.available) return;
            if (lastTouchedRegion !== region) {
                e.preventDefault();
                lastTouchedRegion = region;
                // Dispara mouseenter manualmente para mostrar tooltip
                target.dispatchEvent(new MouseEvent('mouseenter'));
            } else {
                window.location.href = '/estadio/sector/' + sector.svg_region;
            }
        }, { passive: false });
    }

    // Actualizar también los botones "O elige por zona" para que vayan a la página de butacas
    document.querySelectorAll('button[data-sector]').forEach((btn) => {
        btn.onclick = null;
        btn.addEventListener('click', () => {
            try {
                const data = JSON.parse(btn.dataset.sector);
                if (data && data.svg_region) {
                    window.location.href = '/estadio/sector/' + data.svg_region;
                }
            } catch (e) { /* ignore */ }
        });
    });
})();
</script>
@endpush

@endsection

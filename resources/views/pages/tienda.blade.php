@extends('layouts.app')

@section('title', match($type) { 'abono' => 'Abonos', 'entrada' => 'Entradas', 'merch' => 'Equipación', default => 'Tienda oficial' })
@section('description', 'Tienda oficial Algeciras CF. Equipación Capelli, abonos 2026-27, entradas y merchandising.')

@section('content')

{{-- =====================================================
     HERO TIENDA — gigante con badge superpuesto
     ===================================================== --}}
<section class="relative bg-algeciras-black text-white overflow-hidden h-[55vh] min-h-[460px] flex items-end">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>

    {{-- Capelli logo + escudo decorativos --}}
    <img src="{{ asset('img/sponsors/capelli.png') }}" alt="" data-fx="hero-badge"
         class="absolute right-8 lg:right-32 top-12 h-16 lg:h-24 opacity-30 pointer-events-none">

    {{-- Capa diagonal roja --}}
    <div data-fx="hero-layer" data-speed="0.4"
         class="absolute -bottom-32 left-0 right-0 h-64 bg-algeciras-red transform -skew-y-3 origin-left opacity-90"></div>

    <div class="relative container mx-auto px-4 lg:px-8 pb-20 z-10" data-fx="hero-text">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-4">Algeciras CF · Tienda oficial</p>
        <h1 class="font-display text-7xl md:text-9xl lg:text-[12rem] leading-[0.85] tracking-tight">
            @switch($type)
                @case('abono')   Abonos @break
                @case('entrada') Entradas @break
                @case('merch')   Equipación @break
                @default         Tienda
            @endswitch
        </h1>
        <p class="mt-6 text-lg text-algeciras-bone/80 max-w-xl">
            <strong class="text-white" data-fx="counter" data-value="{{ $products->count() }}">0</strong> {{ Str::plural('producto', $products->count()) }} oficiales. Marca técnica <strong class="text-algeciras-red">Capelli Sport</strong> hasta 2030.
        </p>
    </div>
</section>

{{-- =====================================================
     FILTROS GRANDES
     ===================================================== --}}
<section class="border-y-2 border-algeciras-black/10 bg-algeciras-cream">
    <div class="container mx-auto px-4 lg:px-8 py-4">
        <nav class="flex flex-wrap gap-2 font-display tracking-widest uppercase text-sm" data-fx="reveal-stagger">
            @php
                $filters = [
                    ['label' => 'Todos',    'route' => 'tienda', 'params' => [],                       'active' => !$type],
                    ['label' => 'Equipación','route' => 'tienda','params' => ['type' => 'merch'],     'active' => $type === 'merch'],
                    ['label' => 'Abonos',   'route' => 'tienda', 'params' => ['type' => 'abono'],     'active' => $type === 'abono'],
                    ['label' => 'Entradas', 'route' => 'tienda', 'params' => ['type' => 'entrada'],   'active' => $type === 'entrada'],
                ];
            @endphp
            @foreach ($filters as $f)
                <a href="{{ route($f['route'], $f['params']) }}"
                   class="px-6 py-3 transition {{ $f['active'] ? 'bg-algeciras-red text-white' : 'border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white' }}">
                    {{ $f['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</section>

{{-- =====================================================
     GRID DE PRODUCTOS — cards grandes con FX cinemático
     ===================================================== --}}
<section class="container mx-auto px-4 lg:px-8 py-16 lg:py-20">
    @if ($products->count())
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8" data-fx="reveal-stagger">
            @foreach ($products as $p)
                @php $hasOffer = $p->compare_at_price && $p->compare_at_price > $p->price; @endphp
                <a href="{{ route('producto', $p->slug) }}"
                   data-fx="tilt"
                   class="group block bg-white border-2 border-transparent hover:border-algeciras-red transition-all relative">

                    {{-- Badges arriba --}}
                    <div class="absolute top-3 left-3 z-10 flex flex-col gap-1.5">
                        @if ($hasOffer)
                            <span class="px-3 py-1 bg-algeciras-red text-white text-xs font-display tracking-widest uppercase">Oferta</span>
                        @endif
                        @if ($p->featured)
                            <span class="px-3 py-1 bg-algeciras-gold text-algeciras-black text-xs font-display tracking-widest uppercase">Destacado</span>
                        @endif
                        @if ($p->type !== 'merch')
                            <span class="px-3 py-1 bg-algeciras-black text-white text-xs font-display tracking-widest uppercase">{{ $p->type }}</span>
                        @endif
                    </div>

                    {{-- Imagen con image-reveal + hover zoom --}}
                    <div class="aspect-square bg-algeciras-bone overflow-hidden relative" data-fx="image-reveal">
                        @if ($p->image)
                            <img src="{{ asset($p->image) }}"
                                 alt="{{ $p->getTranslation('name','es') }}"
                                 class="w-full h-full object-contain p-8 transition-transform duration-700 group-hover:scale-110">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <span class="font-display text-3xl text-algeciras-red/30 px-4 text-center">{{ $p->sku }}</span>
                            </div>
                        @endif

                        {{-- Corte diagonal rojo abajo derecha --}}
                        <div class="absolute -bottom-8 -right-8 w-24 h-24 bg-algeciras-red transform rotate-45 origin-center
                                    group-hover:bottom-0 group-hover:right-0 transition-all duration-500"></div>
                    </div>

                    {{-- Info producto --}}
                    <div class="p-5 lg:p-6">
                        <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-algeciras-red mb-2">
                            @if ($p->category)
                                {{ $p->category->getTranslation('name','es') }}
                            @else
                                {{ $p->type }}
                            @endif
                        </p>
                        <h3 class="font-display text-xl lg:text-2xl leading-tight mb-3 min-h-[3rem]">{{ $p->getTranslation('name','es') }}</h3>

                        <div class="flex items-baseline gap-2">
                            <span class="font-display text-3xl text-algeciras-red">{{ number_format((float)$p->price, 2, ',', '.') }}€</span>
                            @if ($hasOffer)
                                <span class="text-sm text-algeciras-gray line-through">{{ number_format((float)$p->compare_at_price, 2, ',', '.') }}€</span>
                            @endif
                        </div>

                        @if ($p->capacity && $p->remaining !== null && $p->remaining < 50)
                            <p class="text-xs text-algeciras-red mt-2 font-display tracking-widest uppercase">¡Quedan {{ $p->remaining }}!</p>
                        @elseif ($p->has_variants)
                            <p class="text-xs text-algeciras-gray mt-2">Tallas XS - XXL</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-24" data-fx="reveal">
            <p class="font-display text-4xl mb-4">Sin productos en esta categoría</p>
            <p class="text-algeciras-gray mb-8">Estamos preparando nuevas referencias.</p>
            <a href="{{ route('tienda') }}" class="inline-block px-8 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                Ver todos los productos
            </a>
        </div>
    @endif
</section>

{{-- =====================================================
     CALL TO ACTION FINAL
     ===================================================== --}}
@if ($type !== 'abono')
<section class="bg-algeciras-red text-white py-20 relative overflow-hidden">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <div class="container mx-auto px-4 lg:px-8 relative z-10 text-center" data-fx="reveal">
        <p class="font-mono text-white/70 text-sm tracking-[0.4em] uppercase mb-4">¿Aún no eres abonado?</p>
        <h2 class="font-display text-5xl md:text-7xl mb-6">Tu sitio en el Mirador</h2>
        <p class="text-xl mb-8 max-w-2xl mx-auto">19 partidos de Primera RFEF. Renovación 15 jun. Captación nuevos desde 65€.</p>
        <a href="{{ route('abonos') }}" class="inline-block px-10 py-5 bg-algeciras-black hover:bg-algeciras-ash text-white font-display tracking-widest uppercase text-lg shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
            Hazte abonado →
        </a>
    </div>
</section>
@endif

@endsection

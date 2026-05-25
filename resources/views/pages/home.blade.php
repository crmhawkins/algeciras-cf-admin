@extends('layouts.app')

@section('title', 'Web oficial')
@section('description', 'Algeciras Club de Fútbol. Temporada 2026-27. Tienda oficial, abonos, entradas y noticias del primer equipo. #CrecemosContigo')

@section('content')

{{-- =====================================================
     HERO BRUTAL
     ===================================================== --}}
<section class="relative bg-algeciras-black text-white overflow-hidden">
    {{-- Capa de grano + acento rojo lateral --}}
    <div class="absolute inset-0 grano opacity-40 pointer-events-none"></div>
    <div class="absolute top-0 right-0 bottom-0 w-1/3 bg-algeciras-red transform skew-x-12 -translate-x-12 opacity-90"></div>
    <div class="absolute top-0 right-0 bottom-0 w-1/4 bg-algeciras-red-dark transform skew-x-12 translate-x-32"></div>

    <div class="relative container mx-auto px-4 lg:px-8 py-20 lg:py-32 grid lg:grid-cols-12 gap-10 items-center">
        <div class="lg:col-span-7 z-10">
            <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] mb-4 uppercase">{{ env('CLUB_HASHTAG') }} · Temporada {{ env('CLUB_SEASON') }}</p>
            <h1 class="font-display text-7xl md:text-8xl lg:text-[9rem] leading-[0.85] tracking-tight mb-6">
                Algeciras<br>
                <span class="text-algeciras-red text-shadow-brutal">Club de</span><br>
                Fútbol
            </h1>
            <p class="text-lg md:text-xl max-w-xl text-algeciras-bone/90 mb-8 leading-relaxed">
                <strong class="text-white">117 años</strong> de historia. Una sola razón: <strong class="text-algeciras-red">la afición</strong>. Hazte abonado, vive cada partido y lleva el escudo allá donde vayas.
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="#" class="inline-block px-8 py-4 bg-algeciras-red hover:bg-algeciras-red-light transition font-display tracking-widest uppercase text-lg shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none">
                    Hazte abonado →
                </a>
                <a href="#" class="inline-block px-8 py-4 border-2 border-white hover:bg-white hover:text-algeciras-black transition font-display tracking-widest uppercase text-lg">
                    Comprar entradas
                </a>
            </div>
        </div>

        <div class="lg:col-span-5 relative z-10">
            <img src="{{ asset('img/club/escudo.png') }}" alt="Escudo" class="w-full max-w-md mx-auto drop-shadow-[0_20px_30px_rgba(0,0,0,0.6)]">
        </div>
    </div>

    {{-- Cinta inferior con stats --}}
    <div class="relative border-t-2 border-algeciras-red bg-algeciras-ash">
        <div class="container mx-auto px-4 lg:px-8 py-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
                <div class="font-display font-black text-4xl text-algeciras-red">1912</div>
                <div class="text-xs uppercase tracking-widest text-white/60">Año de fundación</div>
            </div>
            <div>
                <div class="font-display font-black text-4xl text-algeciras-red">9</div>
                <div class="text-xs uppercase tracking-widest text-white/60">Temporadas en 2ª A</div>
            </div>
            <div>
                <div class="font-display font-black text-4xl text-algeciras-red">1ª RFEF</div>
                <div class="text-xs uppercase tracking-widest text-white/60">Categoría 26-27</div>
            </div>
            <div>
                <div class="font-display font-black text-4xl text-algeciras-red">Mirador</div>
                <div class="text-xs uppercase tracking-widest text-white/60">Nuestra casa</div>
            </div>
        </div>
    </div>
</section>

{{-- =====================================================
     PRÓXIMO PARTIDO — countdown placeholder
     ===================================================== --}}
<section class="container mx-auto px-4 lg:px-8 py-20">
    <div class="bg-algeciras-black text-white p-10 md:p-16 grid md:grid-cols-3 gap-8 items-center clip-tarjeta">
        <div>
            <p class="font-mono text-algeciras-red text-xs tracking-widest uppercase mb-2">Próximo partido — Jornada 1</p>
            <h2 class="font-display text-5xl md:text-6xl">Algeciras CF</h2>
            <p class="font-display text-3xl text-algeciras-bone/60 my-2">VS</p>
            <h2 class="font-display text-5xl md:text-6xl text-algeciras-red">Rival próximamente</h2>
            <p class="mt-4 text-sm text-algeciras-bone/70">Estadio Nuevo Mirador · 29 ago 2026</p>
        </div>
        <div class="md:col-span-2 grid grid-cols-4 gap-3">
            @foreach (['Días' => 96, 'Horas' => 14, 'Min' => 33, 'Seg' => 12] as $label => $val)
                <div class="bg-algeciras-red text-white p-4 md:p-6 text-center">
                    <div class="font-display text-4xl md:text-6xl">{{ $val }}</div>
                    <div class="text-xs uppercase tracking-widest opacity-80">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- =====================================================
     CATEGORÍAS DE TIENDA
     ===================================================== --}}
<section class="container mx-auto px-4 lg:px-8 py-20">
    <div class="flex items-end justify-between mb-12 flex-wrap gap-4">
        <div>
            <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">Tienda oficial</p>
            <h2 class="font-display text-6xl">Vístete del escudo</h2>
        </div>
        <a href="#" class="font-display tracking-widest uppercase text-sm border-b-2 border-algeciras-red hover:border-algeciras-black">Ver todo →</a>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ([
            ['cat' => 'Abonos', 'sub' => '2026-27', 'color' => 'bg-algeciras-red text-white'],
            ['cat' => 'Entradas', 'sub' => 'Partido a partido', 'color' => 'bg-algeciras-black text-white'],
            ['cat' => 'Equipación', 'sub' => 'Capelli 26-27', 'color' => 'bg-algeciras-gold text-algeciras-black'],
            ['cat' => 'Lifestyle', 'sub' => 'Bufandas · Gorras', 'color' => 'bg-algeciras-cream text-algeciras-black border-2 border-algeciras-black'],
        ] as $c)
            <a href="#" class="group {{ $c['color'] }} p-8 aspect-square flex flex-col justify-between clip-tarjeta hover:translate-x-1 hover:translate-y-1 transition">
                <p class="font-mono text-xs tracking-widest uppercase opacity-80">{{ $c['sub'] }}</p>
                <div>
                    <h3 class="font-display text-5xl leading-none mb-2">{{ $c['cat'] }}</h3>
                    <span class="inline-block mt-3 text-sm font-display tracking-widest uppercase group-hover:translate-x-2 transition">Ver →</span>
                </div>
            </a>
        @endforeach
    </div>
</section>

{{-- =====================================================
     ÚLTIMAS NOTICIAS — placeholders
     ===================================================== --}}
<section class="bg-algeciras-black text-white py-20">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="flex items-end justify-between mb-12 flex-wrap gap-4">
            <div>
                <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">Actualidad</p>
                <h2 class="font-display text-6xl">Lo último del club</h2>
            </div>
            <a href="#" class="font-display tracking-widest uppercase text-sm border-b-2 border-algeciras-red hover:border-white">Ver todas →</a>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            @for ($i = 1; $i <= 3; $i++)
                <article class="bg-algeciras-ash hover:bg-algeciras-red transition group cursor-pointer">
                    <div class="aspect-[4/3] bg-gradient-to-br from-algeciras-red/40 to-algeciras-black flex items-center justify-center">
                        <img src="{{ asset('img/club/escudo.png') }}" alt="" class="h-24 w-auto opacity-30 group-hover:opacity-60 transition">
                    </div>
                    <div class="p-6">
                        <p class="font-mono text-xs tracking-widest uppercase text-algeciras-red group-hover:text-white mb-2">25 mayo 2026</p>
                        <h3 class="font-display text-2xl mb-2 leading-tight">Noticia destacada {{ $i }} del primer equipo</h3>
                        <p class="text-sm text-white/70 group-hover:text-white/90">Texto resumen breve de la noticia. Lorem ipsum lorem ipsum.</p>
                    </div>
                </article>
            @endfor
        </div>
    </div>
</section>

{{-- =====================================================
     PATROCINADORES
     ===================================================== --}}
<section class="bg-algeciras-cream py-20">
    <div class="container mx-auto px-4 lg:px-8">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase text-center mb-3">Patrocinador técnico</p>
        <div class="flex justify-center mb-12">
            <div class="bg-algeciras-black px-12 py-6 inline-flex items-center">
                <img src="{{ asset('img/sponsors/capelli.png') }}" alt="Capelli Sport" class="h-12 w-auto">
            </div>
        </div>

        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase text-center mb-8">Patrocinadores oficiales</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 items-center">
            @foreach ([
                ['nombre' => 'Hawkins', 'logo' => 'img/sponsors/hawkins.png', 'invertir' => true],
                ['nombre' => 'Quirónsalud', 'logo' => 'img/sponsors/quironsalud.svg', 'invertir' => false],
                ['nombre' => 'Centro Gráfico', 'logo' => 'img/sponsors/centro-grafico.png', 'invertir' => false],
                ['nombre' => 'EWYT', 'logo' => 'img/sponsors/ewyt.png', 'invertir' => true],
            ] as $s)
                <div class="flex items-center justify-center p-6 hover:opacity-100 opacity-80 transition {{ $s['invertir'] ? 'bg-algeciras-black' : 'bg-white border border-algeciras-black/10' }}">
                    <img src="{{ asset($s['logo']) }}" alt="{{ $s['nombre'] }}" class="h-12 w-auto max-w-full object-contain">
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection

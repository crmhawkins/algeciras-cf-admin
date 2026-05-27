@extends('pages.area-personal._layout')

@section('panel')

<div class="space-y-6">

    {{-- Banner Matchday: si HOY hay partido, link directo al modo matchday --}}
    @if(!empty($matchdayBanner))
        <a href="{{ route('area-personal') }}"
           class="block relative overflow-hidden bg-algeciras-red text-white p-5 shadow-brutal group">
            <div class="absolute inset-0 grano opacity-30 pointer-events-none mix-blend-overlay"></div>
            <div class="relative flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-mono text-white/80 text-[10px] tracking-[0.4em] uppercase mb-1 animate-pulse">
                        · · ·  HOY HAY PARTIDO  · · ·
                    </p>
                    <h3 class="font-display text-2xl md:text-3xl uppercase leading-tight">
                        Algeciras CF vs {{ $matchdayBanner->opponent }}
                    </h3>
                    <p class="text-white/80 text-sm mt-1">
                        {{ optional($matchdayBanner->kickoff_at)->format('H:i') }}h ·
                        {{ $matchdayBanner->stadium ?? 'Nuevo Mirador' }}
                        @if($matchdayBanner->matchday) · Jornada {{ $matchdayBanner->matchday }} @endif
                    </p>
                </div>
                <span class="px-5 py-3 bg-white text-algeciras-red font-display tracking-widest uppercase text-xs group-hover:bg-algeciras-black group-hover:text-white transition">
                    Modo partido →
                </span>
            </div>
        </a>
    @endif

    <header>
        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">Resumen</p>
        <h2 class="font-display text-3xl md:text-4xl uppercase leading-tight">Tu temporada de un vistazo</h2>
    </header>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white border-2 border-algeciras-black/10 p-5">
            <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Abonos</p>
            <p class="font-display text-4xl mt-2">{{ $count_abonos }}</p>
        </div>
        <div class="bg-white border-2 border-algeciras-black/10 p-5">
            <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Entradas</p>
            <p class="font-display text-4xl mt-2">{{ $count_entradas }}</p>
        </div>
        <div class="bg-white border-2 border-algeciras-black/10 p-5">
            <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Compras</p>
            <p class="font-display text-4xl mt-2">{{ $count_compras }}</p>
        </div>
        <div class="bg-white border-2 border-algeciras-red p-5">
            <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-red">Cupones</p>
            <p class="font-display text-4xl mt-2 text-algeciras-red">{{ $cuponesDisponibles }}</p>
        </div>
    </div>

    {{-- Próximo partido --}}
    @if($proximoPartido)
        <div class="bg-algeciras-black text-white p-6 shadow-brutal">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="font-mono text-algeciras-red text-[10px] tracking-[0.4em] uppercase mb-1">Próximo partido</p>
                    <h3 class="font-display text-2xl uppercase">Algeciras CF vs {{ $proximoPartido->opponent }}</h3>
                    <p class="text-algeciras-bone/70 text-sm mt-1">
                        {{ optional($proximoPartido->kickoff_at)->format('d/m/Y H:i') }} ·
                        {{ $proximoPartido->stadium ?? 'Nuevo Mirador' }}
                    </p>
                </div>
                @if($count_abonos > 0)
                    <span class="px-4 py-2 bg-algeciras-red font-display tracking-widest uppercase text-xs">
                        ✓ Acceso con abono
                    </span>
                @else
                    <a href="{{ route('tienda', ['type' => 'entrada']) }}"
                       class="px-5 py-3 bg-algeciras-red hover:bg-algeciras-red-dark font-display tracking-widest uppercase text-xs transition">
                        Comprar entrada →
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- Cards secundarias --}}
    <div class="grid md:grid-cols-2 gap-5">
        <div class="bg-white border-2 border-algeciras-black/10 p-6">
            <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Asistencia esta temporada</p>
            <p class="font-display text-5xl mt-2">{{ number_format($asistencia, 0) }}<span class="text-2xl text-algeciras-gray">%</span></p>
            <div class="mt-3 h-2 bg-algeciras-cream overflow-hidden">
                <div class="h-full bg-algeciras-red" style="width: {{ min(100, max(0, $asistencia)) }}%;"></div>
            </div>
            <p class="text-xs text-algeciras-gray mt-3">Partidos en casa a los que has asistido.</p>
        </div>

        <div class="bg-white border-2 border-algeciras-black/10 p-6">
            <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Votos MVP</p>
            <p class="font-display text-5xl mt-2">{{ $votosMvp }}</p>
            <p class="text-xs text-algeciras-gray mt-3">Veces que has elegido al jugador del partido.</p>
            <a href="{{ route('area-personal.actividad') }}"
               class="inline-block mt-4 text-algeciras-red font-display tracking-wider uppercase text-xs hover:underline">
                Ver mi actividad →
            </a>
        </div>
    </div>

    {{-- Accesos rápidos --}}
    <div class="bg-white border-2 border-algeciras-black/10 p-6">
        <h3 class="font-display text-xl uppercase mb-4">Accesos rápidos</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('area-personal.carnet') }}"
               class="block p-4 bg-algeciras-cream hover:bg-algeciras-red hover:text-white border-2 border-transparent hover:border-algeciras-red transition text-center font-display tracking-wider uppercase text-xs">
                Mi carnet
            </a>
            <a href="{{ route('area-personal.entradas') }}"
               class="block p-4 bg-algeciras-cream hover:bg-algeciras-red hover:text-white border-2 border-transparent hover:border-algeciras-red transition text-center font-display tracking-wider uppercase text-xs">
                Entradas
            </a>
            <a href="{{ route('area-personal.beneficios') }}"
               class="block p-4 bg-algeciras-cream hover:bg-algeciras-red hover:text-white border-2 border-transparent hover:border-algeciras-red transition text-center font-display tracking-wider uppercase text-xs">
                Beneficios
            </a>
            <a href="{{ route('area-personal.datos') }}"
               class="block p-4 bg-algeciras-cream hover:bg-algeciras-red hover:text-white border-2 border-transparent hover:border-algeciras-red transition text-center font-display tracking-wider uppercase text-xs">
                Mis datos
            </a>
        </div>
    </div>
</div>

@endsection

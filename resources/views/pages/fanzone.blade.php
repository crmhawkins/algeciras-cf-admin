@extends('layouts.app')

@section('title', 'FanZone — Vota el MVP')
@section('description', 'Vota al mejor jugador del último partido del Algeciras CF y consulta el historial de MVP de la temporada.')

@section('content')

{{-- =====================================================
     HERO — VOTA EL MVP
     ===================================================== --}}
<section class="relative bg-algeciras-black text-white overflow-hidden h-[55vh] min-h-[440px] flex items-end">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <img src="{{ asset('img/club/escudo.png') }}" alt="" data-fx="hero-badge"
         class="absolute -right-32 top-1/2 -translate-y-1/2 w-[70vh] max-w-none opacity-[0.08] pointer-events-none">

    <div data-fx="hero-layer" data-speed="0.3"
         class="absolute -bottom-32 left-0 right-0 h-64 bg-algeciras-red transform -skew-y-3 origin-left opacity-90"></div>

    <div class="relative container mx-auto px-4 lg:px-8 pb-20 z-10" data-fx="hero-text">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-4">FanZone · Algeciras CF</p>
        <h1 class="font-display text-7xl md:text-9xl lg:text-[12rem] leading-[0.85] tracking-tight">
            Vota el<br><span class="text-algeciras-red">MVP</span>
        </h1>
        <p class="mt-6 text-lg text-algeciras-bone/80 max-w-xl">
            Elige al mejor jugador del partido. Tu voto cuenta.
        </p>
    </div>
</section>

@php
    $isAuth = auth()->check();
@endphp

{{-- =====================================================
     SECCIÓN 1 — PARTIDO ACTUAL + VOTACIÓN
     ===================================================== --}}
<section class="container mx-auto px-4 lg:px-8 py-16 lg:py-24 relative"
         x-data="fanzoneVoting(@js([
             'matchId'     => $matchActivo?->id,
             'isAuth'      => $isAuth,
             'votosUrl'    => $matchActivo ? url('/api/fanzone/'.$matchActivo->id.'/votos') : null,
             'miVotoUrl'   => $matchActivo && $isAuth ? url('/api/fanzone/'.$matchActivo->id.'/mi-voto') : null,
             'votarUrl'    => $matchActivo && $isAuth ? url('/api/fanzone/'.$matchActivo->id.'/votar') : null,
             'csrfToken'   => csrf_token(),
         ]))"
         x-init="init()">

    <div class="absolute -top-8 right-0 lg:right-12 pointer-events-none select-none" aria-hidden="true">
        <span class="font-display text-[20rem] lg:text-[28rem] leading-none text-algeciras-red/[0.04]">01</span>
    </div>

    <div class="relative flex items-end justify-between mb-10 flex-wrap gap-4 z-10">
        <div data-fx="reveal">
            <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">01 / 02</p>
            <h2 class="font-display text-5xl md:text-7xl">Partido actual</h2>
        </div>
        @if ($matchActivo)
            <div class="text-right font-display" data-fx="reveal">
                <p class="text-sm font-mono text-algeciras-bone/60 tracking-widest uppercase">
                    {{ $matchActivo->status === 'finished' ? 'Último jugado' : 'Próximo' }}
                </p>
                <p class="text-2xl text-algeciras-red">
                    {{ $matchActivo->kickoff_at?->translatedFormat('d M Y') }}
                </p>
            </div>
        @endif
    </div>

    @if (!$matchActivo)
        <div class="bg-algeciras-ash/50 border-l-4 border-algeciras-red p-8 relative z-10">
            <p class="font-display text-2xl text-algeciras-bone">No hay ningún partido para votar ahora mismo.</p>
            <p class="text-algeciras-bone/70 mt-2">Vuelve cuando se acerque la próxima jornada.</p>
        </div>
    @else
        {{-- Cabecera del partido --}}
        <div class="relative z-10 bg-algeciras-black text-white p-8 md:p-12 mb-10 border-l-4 border-algeciras-red">
            <div class="flex items-center justify-between flex-wrap gap-6">
                <div class="flex-1 text-center md:text-left">
                    <p class="font-mono text-xs text-algeciras-red tracking-[0.3em] uppercase mb-2">
                        {{ $matchActivo->venue === 'home' ? 'En El Mirador' : 'Visitante' }} · J{{ $matchActivo->matchday ?? '—' }}
                    </p>
                    <h3 class="font-display text-3xl md:text-5xl leading-tight">
                        @if ($matchActivo->venue === 'home')
                            Algeciras CF <span class="text-algeciras-red">vs</span> {{ $matchActivo->opponent }}
                        @else
                            {{ $matchActivo->opponent }} <span class="text-algeciras-red">vs</span> Algeciras CF
                        @endif
                    </h3>
                </div>
                @if ($matchActivo->status === 'finished')
                    <div class="text-center">
                        <p class="font-mono text-xs text-algeciras-bone/60 tracking-widest uppercase mb-1">Resultado</p>
                        <p class="font-display text-5xl md:text-6xl tracking-tight">
                            {{ $matchActivo->home_score ?? '-' }} <span class="text-algeciras-red">:</span> {{ $matchActivo->away_score ?? '-' }}
                        </p>
                    </div>
                @endif
                <div class="text-center">
                    <p class="font-mono text-xs text-algeciras-bone/60 tracking-widest uppercase mb-1">Votos totales</p>
                    <p class="font-display text-5xl text-algeciras-red" x-text="totalVotos">0</p>
                </div>
            </div>
        </div>

        {{-- Mensaje "voto registrado" --}}
        <template x-if="miVoto">
            <div class="relative z-10 mb-8 p-4 bg-algeciras-red/10 border-l-4 border-algeciras-red font-display text-lg uppercase tracking-wider">
                Tu voto: <span class="text-algeciras-red" x-text="miVotoNombre"></span>
            </div>
        </template>

        {{-- Mensaje login --}}
        @guest
            <div class="relative z-10 mb-8 p-4 bg-algeciras-ash/30 border-l-4 border-algeciras-bone/40 font-display text-base tracking-wider text-algeciras-bone">
                Para votar tienes que tener cuenta de socio.
                <a href="{{ route('area-personal') }}" class="text-algeciras-red underline hover:no-underline ml-1">
                    Inicia sesión →
                </a>
            </div>
        @endguest

        {{-- Grid de jugadores --}}
        <div class="relative z-10 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 lg:gap-8" data-fx="reveal-stagger">
            @foreach ($jugadores as $p)
                @php
                    $playerJson = [
                        'id'     => $p->id,
                        'nombre' => $p->display_name,
                        'dorsal' => $p->dorsal,
                    ];
                @endphp
                <button
                    type="button"
                    @click="votar(@js($playerJson))"
                    :disabled="votando || !{{ $isAuth ? 'true' : 'false' }}"
                    :class="miVoto === {{ $p->id }} ? 'ring-4 ring-algeciras-red' : ''"
                    class="group relative bg-algeciras-black text-white overflow-hidden cursor-pointer text-left transition shadow-brutal hover:-translate-y-1 hover:shadow-brutal-lg disabled:opacity-60 disabled:cursor-not-allowed">

                    <div class="aspect-[3/4] bg-gradient-to-br from-algeciras-ash to-algeciras-black overflow-hidden">
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

                    <div class="absolute inset-0 bg-gradient-to-t from-algeciras-black via-algeciras-black/30 to-transparent pointer-events-none"></div>
                    <div class="absolute top-0 right-0 w-20 h-20 bg-algeciras-red transform rotate-45 translate-x-10 -translate-y-10 group-hover:translate-x-6 group-hover:-translate-y-6 transition-transform duration-500"></div>

                    <div class="absolute bottom-0 left-0 right-0 p-4 lg:p-5">
                        <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-algeciras-red mb-1">
                            #{{ $p->dorsal ?? '—' }} · {{ $p->position }}
                        </p>
                        <h3 class="font-display text-xl lg:text-2xl leading-tight mb-2">{{ $p->display_name }}</h3>

                        {{-- Barra de votos --}}
                        <div class="mt-2" x-show="getPct({{ $p->id }}) > 0">
                            <div class="flex justify-between items-baseline font-mono text-[10px] uppercase tracking-widest text-algeciras-bone/80 mb-1">
                                <span x-text="getVotos({{ $p->id }}) + ' votos'"></span>
                                <span class="text-algeciras-red font-bold" x-text="getPct({{ $p->id }}) + '%'"></span>
                            </div>
                            <div class="h-1.5 bg-white/10 overflow-hidden">
                                <div class="h-full bg-algeciras-red transition-all duration-700"
                                     :style="`width: ${getPct({{ $p->id }})}%`"></div>
                            </div>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>
    @endif
</section>

{{-- =====================================================
     SECCIÓN 2 — HISTORIAL MVP
     ===================================================== --}}
<section class="bg-algeciras-black text-white py-16 lg:py-24 relative overflow-hidden"
         x-data="fanzoneHistorial(@js(['url' => url('/api/fanzone/historial-mvp')]))"
         x-init="load()">

    <div class="absolute -top-8 left-0 lg:left-12 pointer-events-none select-none" aria-hidden="true">
        <span class="font-display text-[20rem] lg:text-[28rem] leading-none text-algeciras-red/[0.06]">02</span>
    </div>

    <div class="container mx-auto px-4 lg:px-8 relative z-10">
        <div class="flex items-end justify-between mb-12 flex-wrap gap-4">
            <div data-fx="reveal">
                <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">02 / 02</p>
                <h2 class="font-display text-5xl md:text-7xl">Historial MVP</h2>
                <p class="mt-3 text-algeciras-bone/70 max-w-xl">
                    Los mejores jugadores votados por la grada partido a partido.
                </p>
            </div>
        </div>

        {{-- Loading --}}
        <template x-if="loading">
            <div class="text-center py-12 text-algeciras-bone/60 font-display tracking-widest uppercase">Cargando…</div>
        </template>

        {{-- Empty --}}
        <template x-if="!loading && historial.length === 0">
            <div class="bg-algeciras-ash/40 border-l-4 border-algeciras-red p-8">
                <p class="font-display text-2xl">Todavía no hay MVP histórico.</p>
                <p class="text-algeciras-bone/70 mt-2">Sé el primero en votar.</p>
            </div>
        </template>

        {{-- Grid de partidos pasados con MVP --}}
        <div x-show="!loading && historial.length > 0"
             class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <template x-for="(item, idx) in historial" :key="item.partido.id">
                <article class="group bg-algeciras-ash/30 border border-white/5 overflow-hidden hover:border-algeciras-red transition">

                    {{-- Foto MVP --}}
                    <div class="relative aspect-[3/4] bg-algeciras-black overflow-hidden">
                        <template x-if="item.mvp.foto">
                            <img :src="item.mvp.foto" :alt="item.mvp.nombre"
                                 class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        </template>
                        <template x-if="!item.mvp.foto">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="font-display text-9xl text-white/10" x-text="item.mvp.dorsal || '?'"></span>
                            </div>
                        </template>
                        <div class="absolute inset-0 bg-gradient-to-t from-algeciras-black via-algeciras-black/30 to-transparent pointer-events-none"></div>

                        {{-- Badge MVP top-left --}}
                        <div class="absolute top-4 left-4 bg-algeciras-red px-3 py-1 font-display tracking-widest text-xs uppercase">
                            ⭐ MVP
                        </div>

                        {{-- Datos abajo --}}
                        <div class="absolute bottom-0 left-0 right-0 p-5">
                            <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-algeciras-red mb-1"
                               x-text="'#' + (item.mvp.dorsal || '—') + ' · ' + item.mvp.position"></p>
                            <h3 class="font-display text-2xl lg:text-3xl leading-tight" x-text="item.mvp.nombre"></h3>
                        </div>
                    </div>

                    {{-- Partido + votos --}}
                    <div class="p-5 flex items-center justify-between gap-3 border-t border-white/5">
                        <div class="min-w-0">
                            <p class="font-mono text-[10px] text-algeciras-bone/60 tracking-widest uppercase mb-1">
                                <span x-text="new Date(item.partido.fecha).toLocaleDateString('es-ES', {day:'2-digit', month:'short', year:'numeric'})"></span>
                            </p>
                            <p class="font-display text-base truncate"
                               x-text="item.partido.venue === 'home' ? 'Algeciras CF vs ' + item.partido.rival : item.partido.rival + ' vs Algeciras CF'"></p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-display text-3xl text-algeciras-red leading-none" x-text="item.votos"></p>
                            <p class="font-mono text-[9px] text-algeciras-bone/60 tracking-widest uppercase">votos</p>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        // === Votación del partido actual ===
        Alpine.data('fanzoneVoting', (config) => ({
            matchId: config.matchId,
            isAuth: !!config.isAuth,
            votosUrl: config.votosUrl,
            miVotoUrl: config.miVotoUrl,
            votarUrl: config.votarUrl,
            csrfToken: config.csrfToken,
            votos: [],           // [{jugador:{id,nombre}, votos, porcentaje}]
            totalVotos: 0,
            miVoto: null,        // player id
            miVotoNombre: '',
            votando: false,

            async init() {
                if (!this.matchId) return;
                await this.loadVotos();
                if (this.isAuth && this.miVotoUrl) await this.loadMiVoto();
            },

            async loadVotos() {
                try {
                    const r = await fetch(this.votosUrl, { headers: { 'Accept': 'application/json' }});
                    if (!r.ok) return;
                    const data = await r.json();
                    this.votos = data.resultado || [];
                    this.totalVotos = data.total || 0;
                } catch (e) { /* silencioso */ }
            },

            async loadMiVoto() {
                try {
                    const r = await fetch(this.miVotoUrl, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!r.ok) return;
                    const data = await r.json();
                    if (data.voto) {
                        this.miVoto = data.voto;
                        this.miVotoNombre = data.jugador?.nombre ?? '';
                    }
                } catch (e) { /* silencioso */ }
            },

            getVotos(playerId) {
                const v = this.votos.find(x => x.jugador?.id === playerId);
                return v ? v.votos : 0;
            },

            getPct(playerId) {
                const v = this.votos.find(x => x.jugador?.id === playerId);
                return v ? v.porcentaje : 0;
            },

            async votar(player) {
                if (!this.isAuth) {
                    window.location.href = "{{ route('area-personal') }}";
                    return;
                }
                if (this.votando || !this.votarUrl) return;
                this.votando = true;
                try {
                    const r = await fetch(this.votarUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ jugador: player.id }),
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || data.ok === false) {
                        alert(data.msg || data.message || 'No se ha podido registrar el voto.');
                        return;
                    }
                    this.miVoto = player.id;
                    this.miVotoNombre = player.nombre;
                    await this.loadVotos();
                } catch (e) {
                    alert('Error de red al votar.');
                } finally {
                    this.votando = false;
                }
            },
        }));

        // === Historial MVP ===
        Alpine.data('fanzoneHistorial', (config) => ({
            url: config.url,
            historial: [],
            loading: true,

            async load() {
                try {
                    const r = await fetch(this.url, { headers: { 'Accept': 'application/json' }});
                    if (!r.ok) return;
                    this.historial = await r.json();
                } catch (e) { /* silencioso */ }
                finally { this.loading = false; }
            },
        }));
    });
</script>
@endpush

@endsection

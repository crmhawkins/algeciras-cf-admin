@extends('layouts.app')

@section('title', '¡Hoy hay partido! — Algeciras CF vs '.$match->opponent)
@section('description', 'Día de partido. Tu QR de acceso, asiento y todo lo que necesitas para vivir el Nuevo Mirador.')

@section('content')

{{-- ============== HERO MATCHDAY ============== --}}
<section class="relative min-h-screen bg-algeciras-red text-white overflow-hidden">

    {{-- Grano + degradado --}}
    <div class="absolute inset-0 grano opacity-40 pointer-events-none mix-blend-overlay"></div>
    <div class="absolute inset-0 pointer-events-none"
         style="background: radial-gradient(ellipse at top, rgba(0,0,0,0) 0%, rgba(0,0,0,0.55) 100%);"></div>

    {{-- Diagonal decorativa --}}
    <div data-fx="hero-layer" data-speed="0.5"
         class="absolute -top-32 -right-24 w-[140%] h-64 bg-algeciras-black transform -rotate-3 origin-top-right opacity-60"></div>

    <div class="relative z-10 container mx-auto px-4 lg:px-8 py-10 lg:py-16">

        {{-- Cabecera --}}
        <div class="flex flex-wrap items-center justify-between gap-4 mb-10">
            <a href="{{ route('area-personal') }}?force_dashboard=1"
               class="inline-flex items-center gap-2 text-white/80 hover:text-white font-mono text-xs tracking-[0.3em] uppercase">
                ← Volver al área
            </a>
            <p class="font-mono text-white/70 text-xs tracking-[0.4em] uppercase">
                {{ $match->competition ?? 'Primera RFEF' }}
                @if($match->matchday) · Jornada {{ $match->matchday }} @endif
            </p>
        </div>

        {{-- Bloque principal --}}
        <div class="text-center mb-14">
            <p class="font-mono text-white/80 text-xs sm:text-sm tracking-[0.5em] uppercase mb-4 animate-pulse">
                · · ·  HOY HAY PARTIDO  · · ·
            </p>
            <h1 class="font-display uppercase leading-none text-5xl sm:text-7xl md:text-8xl lg:text-9xl"
                style="text-shadow: 0 6px 30px rgba(0,0,0,0.45);">
                ¡A POR <span class="block">ELLOS!</span>
            </h1>
            <p class="mt-8 font-display text-2xl sm:text-3xl md:text-4xl uppercase tracking-wider">
                Algeciras C.F. <span class="text-white/60 mx-3">vs</span> {{ $match->opponent }}
            </p>
            <p class="mt-3 text-white/70 text-sm">
                {{ $match->stadium ?? 'Nuevo Mirador' }} · {{ optional($match->kickoff_at)->translatedFormat('l d \d\e F · H:i') }}h
            </p>
        </div>

        {{-- Countdown --}}
        @if($match->kickoff_at && $match->kickoff_at->isFuture())
        <div class="text-center mb-16" id="matchday-countdown" data-kickoff="{{ $match->kickoff_at->toIso8601String() }}">
            <p class="font-mono text-white/80 text-xs tracking-[0.4em] uppercase mb-3">Faltan</p>
            <div class="inline-flex items-baseline gap-3 sm:gap-6 font-display text-6xl sm:text-8xl md:text-9xl tabular-nums"
                 style="text-shadow: 0 4px 20px rgba(0,0,0,0.4);">
                <span data-cd="hh">00</span>
                <span class="text-white/40 text-4xl sm:text-6xl">:</span>
                <span data-cd="mm">00</span>
                <span class="text-white/40 text-4xl sm:text-6xl">:</span>
                <span data-cd="ss">00</span>
            </div>
            <div class="flex justify-center gap-12 sm:gap-20 mt-2 font-mono text-[10px] sm:text-xs tracking-[0.4em] text-white/60">
                <span>HORAS</span><span>MIN</span><span>SEG</span>
            </div>
        </div>
        @elseif($match->status === 'live')
        <div class="text-center mb-16">
            <span class="inline-flex items-center gap-3 px-6 py-3 bg-white/10 backdrop-blur border-2 border-white font-display uppercase tracking-widest">
                <span class="w-3 h-3 bg-white rounded-full animate-pulse"></span> En directo
            </span>
        </div>
        @endif

        {{-- ============== ESTADO DEL TICKET ============== --}}
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-start">

            {{-- Columna izquierda: QR o CTA compra --}}
            <div class="bg-white text-algeciras-black p-6 sm:p-10 shadow-2xl">
                @if($hasTicket)
                    <div class="flex items-center justify-between mb-4">
                        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase">
                            @if($isAbono) Tu abono @else Tu entrada @endif
                        </p>
                        <span class="px-3 py-1 bg-algeciras-black text-white text-[10px] font-mono tracking-widest uppercase">
                            {{ optional($ticket?->product)->name ?? ($isAbono ? 'Abono' : 'Entrada') }}
                        </span>
                    </div>

                    <h2 class="font-display text-2xl uppercase mb-6">Escanea en el torno</h2>

                    <div class="aspect-square w-full max-w-md mx-auto bg-white border-4 border-algeciras-black flex items-center justify-center overflow-hidden">
                        {{-- $qr ya es un <svg>...</svg> generado por endroid; lo inyectamos crudo --}}
                        {!! $qr ?? '' !!}
                    </div>

                    <dl class="grid grid-cols-3 gap-3 mt-6 text-center">
                        <div class="border-2 border-algeciras-black/10 p-3">
                            <dt class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Sector</dt>
                            <dd class="font-display text-lg mt-1">{{ $sector ?? '—' }}</dd>
                        </div>
                        <div class="border-2 border-algeciras-black/10 p-3">
                            <dt class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Fila</dt>
                            <dd class="font-display text-lg mt-1">{{ $row ?? '—' }}</dd>
                        </div>
                        <div class="border-2 border-algeciras-black/10 p-3">
                            <dt class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Butaca</dt>
                            <dd class="font-display text-lg mt-1">{{ $seat ?? '—' }}</dd>
                        </div>
                    </dl>

                    @if($isAbono && $ticket)
                        {{-- Liberar plaza (UI only, lógica POST pendiente) --}}
                        <form method="POST" action="/area-personal/abonos/liberar/{{ $ticket->id }}" class="mt-6">
                            @csrf
                            <button type="submit"
                                    class="w-full px-5 py-4 border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white transition font-display tracking-widest uppercase text-sm">
                                ¿No vas a venir? Libera tu plaza →
                            </button>
                            <p class="mt-2 text-[11px] text-algeciras-gray text-center">
                                Cede tu butaca al club por un partido. Recibirás puntos socio.
                            </p>
                        </form>
                    @endif
                @else
                    {{-- Sin abono ni entrada — CTA compra --}}
                    <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-3">No te lo pierdas</p>
                    <h2 class="font-display text-3xl sm:text-4xl uppercase mb-4">Aún estás a tiempo.</h2>
                    <p class="text-algeciras-gray mb-8">
                        Compra tu entrada ahora y vente al Nuevo Mirador a empujar al equipo.
                    </p>
                    <a href="{{ url('/tienda?type=entrada') }}"
                       class="block text-center px-8 py-5 bg-algeciras-red text-white hover:bg-algeciras-black transition font-display tracking-widest uppercase text-base sm:text-lg">
                        Comprar entrada →
                    </a>
                    <a href="{{ route('abonos') }}"
                       class="block text-center mt-3 px-8 py-4 border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white transition font-display tracking-widest uppercase text-xs">
                        O hazte abonado para toda la temporada
                    </a>
                @endif
            </div>

            {{-- Columna derecha: info partido + mapa --}}
            <div class="space-y-6">

                <div class="bg-algeciras-black/40 backdrop-blur border border-white/10 p-6 sm:p-8">
                    <p class="font-mono text-white/60 text-[11px] tracking-[0.4em] uppercase mb-2">Apertura de puertas</p>
                    <p class="font-display text-3xl sm:text-4xl">
                        @if($gatesOpenAt)
                            {{ $gatesOpenAt->format('H:i') }}h
                        @else
                            —
                        @endif
                    </p>
                    <p class="text-white/70 text-xs mt-2">
                        Te recomendamos llegar con al menos 30 minutos de antelación para evitar colas en los tornos.
                    </p>
                </div>

                <div class="bg-algeciras-black/40 backdrop-blur border border-white/10 p-6 sm:p-8">
                    <p class="font-mono text-white/60 text-[11px] tracking-[0.4em] uppercase mb-3">Cómo llegar</p>
                    <div class="aspect-video w-full overflow-hidden border border-white/10">
                        <iframe
                            src="https://www.google.com/maps?q=Estadio+Nuevo+Mirador+Algeciras+Cadiz&output=embed"
                            width="100%" height="100%" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            style="border:0" allowfullscreen></iframe>
                    </div>
                    <a href="https://maps.google.com/?q=Estadio+Nuevo+Mirador+Algeciras"
                       target="_blank" rel="noopener"
                       class="block text-center mt-4 px-5 py-3 bg-white text-algeciras-black hover:bg-algeciras-bone transition font-display tracking-widest uppercase text-xs">
                        Abrir en Google Maps →
                    </a>
                </div>

                @if($match->broadcast)
                <div class="bg-algeciras-black/40 backdrop-blur border border-white/10 p-6 sm:p-8">
                    <p class="font-mono text-white/60 text-[11px] tracking-[0.4em] uppercase mb-2">¿No puedes venir?</p>
                    <p class="font-display text-2xl">{{ $match->broadcast }}</p>
                    <p class="text-white/70 text-xs mt-2">Síguelo por la retransmisión oficial.</p>
                </div>
                @endif
            </div>
        </div>

    </div>
</section>

{{-- Countdown JS — actualiza cada segundo --}}
<script>
(function () {
    var el = document.getElementById('matchday-countdown');
    if (!el) return;
    var kickoff = new Date(el.dataset.kickoff).getTime();
    var hh = el.querySelector('[data-cd="hh"]');
    var mm = el.querySelector('[data-cd="mm"]');
    var ss = el.querySelector('[data-cd="ss"]');

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function tick() {
        var diff = Math.max(0, kickoff - Date.now());
        var totalSec = Math.floor(diff / 1000);
        var h = Math.floor(totalSec / 3600);
        var m = Math.floor((totalSec % 3600) / 60);
        var s = totalSec % 60;
        if (hh) hh.textContent = pad(h);
        if (mm) mm.textContent = pad(m);
        if (ss) ss.textContent = pad(s);
        if (diff <= 0) {
            clearInterval(interval);
        }
    }
    tick();
    var interval = setInterval(tick, 1000);
})();
</script>

@endsection

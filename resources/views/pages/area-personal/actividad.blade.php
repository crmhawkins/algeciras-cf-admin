@extends('pages.area-personal._layout')

@section('panel')

<div class="space-y-6">
    <header>
        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">Mi Actividad</p>
        <h2 class="font-display text-3xl md:text-4xl uppercase leading-tight">Tu historial como aficionado</h2>
    </header>

    <div class="grid lg:grid-cols-2 gap-6">

        {{-- ============== VOTOS MVP ============== --}}
        <section class="bg-white border-2 border-algeciras-black/10">
            <header class="px-5 py-3 bg-algeciras-black text-white flex items-center justify-between">
                <h3 class="font-display tracking-widest uppercase text-sm">Votos MVP</h3>
                <span class="font-mono text-xs">{{ $votos->count() }}</span>
            </header>

            @if($votos->isEmpty())
                <div class="p-8 text-center text-sm text-algeciras-gray">
                    <p class="text-3xl mb-2">⭐</p>
                    <p>Aún no has votado a ningún MVP. <br>Hazlo después de cada partido en <a href="{{ route('fanzone') }}" class="text-algeciras-red hover:underline">FanZone</a>.</p>
                </div>
            @else
                <ol class="divide-y divide-algeciras-black/5">
                    @foreach($votos as $v)
                        <li class="flex items-start gap-3 p-4">
                            <div class="w-10 h-10 flex-shrink-0 bg-algeciras-red text-white grid place-items-center font-display">
                                ⭐
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-display text-base leading-tight">{{ optional($v->player)->name ?? 'Jugador' }}</p>
                                <p class="text-xs text-algeciras-gray">
                                    @if($v->match)
                                        vs {{ $v->match->opponent }} · {{ optional($v->match->kickoff_at)->format('d/m/Y') }}
                                    @else
                                        {{ optional($v->created_at)->format('d/m/Y H:i') }}
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        {{-- ============== ASISTENCIAS ============== --}}
        <section class="bg-white border-2 border-algeciras-black/10">
            <header class="px-5 py-3 bg-algeciras-black text-white flex items-center justify-between">
                <h3 class="font-display tracking-widest uppercase text-sm">Partidos asistidos</h3>
                <span class="font-mono text-xs">{{ $asistencias->count() }}</span>
            </header>

            @if($asistencias->isEmpty())
                <div class="p-8 text-center text-sm text-algeciras-gray">
                    <p class="text-3xl mb-2">🏟️</p>
                    <p>Aún no se ha registrado ninguna asistencia.<br>Pasarás por el torno y aparecerá aquí.</p>
                </div>
            @else
                <ol class="divide-y divide-algeciras-black/5">
                    @foreach($asistencias as $a)
                        @php $m = $a->match; @endphp
                        <li class="flex items-start gap-3 p-4">
                            <div class="w-10 h-10 flex-shrink-0 bg-algeciras-black text-white grid place-items-center font-display">
                                🏟️
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-display text-base leading-tight">
                                    @if($m)
                                        Algeciras CF vs {{ $m->opponent }}
                                    @else
                                        Partido
                                    @endif
                                </p>
                                <p class="text-xs text-algeciras-gray">
                                    {{ optional($a->checked_in_at)->format('d/m/Y H:i') }}
                                    @if($m && $m->result) · {{ $m->result }} @endif
                                    @if($a->gate) · Acceso {{ $a->gate }} @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</div>

@endsection

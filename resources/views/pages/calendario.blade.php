@extends('layouts.app')

@section('title', 'Calendario y resultados')

@section('content')
<section class="bg-algeciras-black text-white py-16">
    <div class="container mx-auto px-4 lg:px-8">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">Primera RFEF 2026-27</p>
        <h1 class="font-display text-6xl md:text-7xl">Calendario</h1>
    </div>
</section>

<section class="container mx-auto px-4 lg:px-8 py-16">
    <h2 class="font-display text-4xl mb-8">Próximos partidos</h2>
    @if ($upcoming->count())
        <div class="space-y-4">
            @foreach ($upcoming as $m)
                <article class="bg-white border-l-8 border-algeciras-red shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition p-6 grid md:grid-cols-5 gap-4 items-center">
                    <div class="md:col-span-1">
                        <div class="font-mono text-xs uppercase tracking-widest text-algeciras-red">{{ $m->competition }}{{ $m->matchday ? " · J{$m->matchday}" : '' }}</div>
                        <div class="font-display text-2xl">{{ $m->kickoff_at?->isoFormat('D MMM') }}</div>
                        <div class="font-mono text-sm text-algeciras-gray">{{ $m->kickoff_at?->isoFormat('HH:mm') }}h</div>
                    </div>
                    <div class="md:col-span-3 flex items-center gap-4">
                        <span class="font-display text-2xl flex-1 text-right">
                            {{ $m->venue === 'home' ? 'Algeciras CF' : $m->opponent }}
                        </span>
                        <span class="font-display text-3xl text-algeciras-red">VS</span>
                        <span class="font-display text-2xl flex-1">
                            {{ $m->venue === 'home' ? $m->opponent : 'Algeciras CF' }}
                        </span>
                    </div>
                    <div class="md:col-span-1 text-right">
                        <div class="text-xs text-algeciras-gray">{{ $m->stadium }}</div>
                        <a href="{{ route('tienda', ['type' => 'entrada']) }}" class="inline-block mt-2 px-4 py-2 bg-algeciras-red text-white text-xs font-display tracking-widest uppercase hover:bg-algeciras-red-dark">Entradas</a>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <p class="text-algeciras-gray">El calendario oficial 26-27 está pendiente de publicación.</p>
    @endif
</section>

@if ($finished->count())
<section class="bg-algeciras-cream py-16">
    <div class="container mx-auto px-4 lg:px-8">
        <h2 class="font-display text-4xl mb-8">Resultados recientes</h2>
        <div class="grid md:grid-cols-2 gap-4">
            @foreach ($finished as $m)
                <article class="bg-white p-5 flex items-center gap-4">
                    <div class="font-display text-3xl text-algeciras-red">
                        {{ $m->venue === 'home' ? $m->home_score : $m->away_score }} - {{ $m->venue === 'home' ? $m->away_score : $m->home_score }}
                    </div>
                    <div class="flex-1">
                        <div class="font-display text-lg">{{ $m->opponent }}</div>
                        <div class="font-mono text-xs text-algeciras-gray uppercase">{{ $m->competition }} · {{ $m->kickoff_at?->isoFormat('D MMM YYYY') }}</div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection

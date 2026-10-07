@extends('layouts.app')

@section('title', 'Hazte abonado 2026-27')

@section('content')
<section class="bg-algeciras-red text-white py-20 relative overflow-hidden">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <div class="container mx-auto px-4 lg:px-8 relative z-10">
        <p class="font-mono text-white/80 text-sm tracking-[0.4em] uppercase mb-4">Temporada 2026-27</p>
        <h1 class="font-display text-6xl md:text-8xl leading-none">Hazte<br>abonado</h1>
        <p class="text-xl mt-6 max-w-2xl">Tu sitio en el Mirador para los <strong>19 partidos de Primera RFEF</strong>. Renovación 15/JUN, captación nuevos 6/JUL.</p>
    </div>
</section>

@php
    /** @var string|null $tipo */
    $tipo = $tipo ?? null;
@endphp

@if (! $tipo)
    {{-- Selector inicial: ¿renovación o nuevo? Evita que el usuario coja
         el producto equivocado de un grid de 8 cards mezcladas. --}}
    <section class="container mx-auto px-4 lg:px-8 py-20">
        <h2 class="font-display text-4xl md:text-5xl text-center mb-3">¿Renovación o nuevo abonado?</h2>
        <p class="text-center text-algeciras-gray max-w-xl mx-auto mb-12">
            Elige tu situación para que te enseñemos solo las opciones que te corresponden.
        </p>
        <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
            <a href="{{ url('/abonos?tipo=renovacion') }}"
               class="group block bg-white border-l-8 border-algeciras-red p-10 hover:bg-algeciras-red hover:text-white transition shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none">
                <p class="font-mono uppercase tracking-widest text-xs text-algeciras-red group-hover:text-white mb-3">Ya soy socio</p>
                <h3 class="font-display text-3xl mb-3">Renovar mi abono</h3>
                <p class="text-sm text-algeciras-gray group-hover:text-white/80">
                    Mantengo mi asiento de la temporada pasada con tarifa de renovación.
                </p>
                <span class="inline-block mt-6 font-display tracking-widest uppercase text-sm border-b-2 border-algeciras-red group-hover:border-white">Ver tarifas de renovación →</span>
            </a>
            <a href="{{ url('/abonos?tipo=nuevo') }}"
               class="group block bg-white border-l-8 border-algeciras-black p-10 hover:bg-algeciras-black hover:text-white transition shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none">
                <p class="font-mono uppercase tracking-widest text-xs text-algeciras-gray group-hover:text-white/70 mb-3">Soy nuevo</p>
                <h3 class="font-display text-3xl mb-3">Soy un nuevo abonado</h3>
                <p class="text-sm text-algeciras-gray group-hover:text-white/80">
                    Quiero hacerme abonado por primera vez esta temporada.
                </p>
                <span class="inline-block mt-6 font-display tracking-widest uppercase text-sm border-b-2 border-algeciras-black group-hover:border-white">Ver tarifas de captación →</span>
            </a>
        </div>
    </section>
@elseif (! empty($lookup ?? false))
    {{-- Renovación: paso de verificación. Antes de mostrar las cards con
         descuento de socio, pedimos número de abono + DNI y validamos
         contra BD. Solo si encontramos al abonado le dejamos pasar. --}}
    <section class="container mx-auto px-4 lg:px-8 py-16 max-w-2xl">
        <div class="flex items-center gap-3 mb-6 flex-wrap">
            <a href="{{ route('abonos') }}" class="font-display tracking-widest uppercase text-sm text-algeciras-red hover:underline">← Cambiar</a>
            <span class="font-mono tracking-widest uppercase text-xs px-3 py-1 border-2 border-algeciras-red text-algeciras-red">
                Renovación de socios
            </span>
        </div>

        <h2 class="font-display text-4xl mb-3">Verifica tu condición de socio</h2>
        <p class="text-algeciras-gray mb-8">
            Para acceder a las tarifas con descuento de renovación, introduce
            tu número de abono y DNI. Solo si eres socio actual del Algeciras
            CF podrás comprar la tarifa reducida.
        </p>

        <form method="POST" action="{{ route('abonos.renovacion.lookup') }}" class="bg-white border-2 border-algeciras-black/10 p-6 space-y-4 shadow-brutal">
            @csrf

            @if ($errors->any())
                <div class="bg-algeciras-red text-white p-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Número de abonado *</label>
                <input type="number" name="numero_abonado" required min="1"
                       value="{{ old('numero_abonado') }}"
                       placeholder="Ej. 1234"
                       class="w-full px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none font-mono">
                <p class="text-xs text-algeciras-gray mt-1">El que figura en tu carnet de socio.</p>
            </div>

            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">DNI / NIE *</label>
                <input type="text" name="dni" required value="{{ old('dni') }}"
                       placeholder="12345678X"
                       class="w-full px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none font-mono uppercase">
                <p class="text-xs text-algeciras-gray mt-1">El DNI del titular del abono.</p>
            </div>

            <button type="submit" class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                Verificar y continuar →
            </button>

            <p class="text-xs text-algeciras-gray text-center pt-2">
                ¿No eres socio? <a href="{{ url('/abonos?tipo=nuevo') }}" class="text-algeciras-red hover:underline">Ver tarifas de nuevo abonado →</a>
            </p>
        </form>
    </section>
@else
    <section class="container mx-auto px-4 lg:px-8 py-12">
        <div class="flex items-center gap-3 mb-4 flex-wrap">
            <a href="{{ route('abonos') }}" class="font-display tracking-widest uppercase text-sm text-algeciras-red hover:underline">← Cambiar tipo</a>
            <span class="font-mono tracking-widest uppercase text-xs px-3 py-1 border-2 border-algeciras-red text-algeciras-red">
                {{ $tipo === 'renovacion' ? 'Renovación de socios' : 'Captación de nuevos' }}
            </span>
            @if ($tipo === 'renovacion' && ($abonado ?? null))
                <a href="{{ route('abonos.renovacion.reset') }}" class="font-mono tracking-widest uppercase text-xs text-algeciras-gray hover:text-algeciras-red ml-auto">Cambiar abonado</a>
            @endif
        </div>

        @if ($tipo === 'renovacion' && ($abonado ?? null))
            {{-- Card con datos del socio verificado --}}
            <div class="bg-algeciras-cream border-l-4 border-algeciras-red p-5 mb-8">
                <p class="font-mono uppercase tracking-widest text-xs text-algeciras-red mb-1">Socio verificado</p>
                <p class="font-display text-2xl">
                    {{ trim(($abonado['nombre'] ?? '').' '.($abonado['apellidos'] ?? '')) }}
                    <span class="text-algeciras-gray text-base font-normal">· Nº {{ $abonado['numero_abonado'] }}</span>
                </p>
                @if (! empty($abonado['sector_nombre']))
                    <p class="text-sm text-algeciras-gray mt-1">
                        {{ $abonado['sector_nombre'] }}
                        @if (! empty($abonado['fila']))· Fila {{ $abonado['fila'] }}@endif
                        @if (! empty($abonado['asiento']))· Asiento {{ $abonado['asiento'] }}@endif
                        @if (! empty($abonado['season_name']))· Temporada {{ $abonado['season_name'] }}@endif
                    </p>
                @endif
            </div>
        @endif

        @if ($abonos->isEmpty())
            <p class="text-algeciras-gray">No hay tarifas activas para esta opción de momento.</p>
        @else
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($abonos as $a)
                    <article class="bg-white border-l-8 border-algeciras-red p-6 hover:bg-algeciras-black hover:text-white transition group">
                        <div class="flex items-center justify-between mb-3">
                            @if ($a->zone)
                                <span class="px-2 py-1 text-xs font-mono uppercase tracking-widest" style="background-color: {{ $a->zone->color }}; color: white;">{{ $a->zone->name }}</span>
                            @endif
                        </div>
                        <h3 class="font-display text-2xl mb-4 leading-tight">{{ $a->getTranslation('name','es') }}</h3>
                        <div class="font-display text-5xl text-algeciras-red group-hover:text-white">{{ number_format((float)$a->price, 0) }}€</div>
                        {{-- Petición cliente: SOLO precio en banner. Desglose IVA + gestión solo en checkout. --}}
                        <div class="flex flex-col gap-2 mt-4">
                            {{-- Antes saltaba directo al checkout — el cliente
                                 protesta que primero hay que elegir zona y
                                 butaca. Mandamos al plano del estadio con el
                                 product en query; ahí elegirá sector/butaca y
                                 desde la butaca se llega al checkout. --}}
                            <a href="{{ route('estadio', ['product' => $a->slug]) }}"
                               class="inline-block px-4 py-3 bg-algeciras-red text-white font-display tracking-widest uppercase text-sm text-center shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                                Elegir butaca →
                            </a>
                            <a href="{{ route('producto', $a->slug) }}"
                               class="text-xs text-algeciras-gray group-hover:text-white/60 underline text-center">Más info</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endif

<section class="bg-algeciras-cream py-16">
    <div class="container mx-auto px-4 lg:px-8 max-w-3xl">
        <h2 class="font-display text-4xl mb-6">Calendario de la campaña</h2>
        <ul class="space-y-3 text-algeciras-black/80">
            <li class="flex gap-4"><span class="font-display text-algeciras-red w-32 shrink-0">01-06 JUN</span> Teaser pre-anuncio / lista de espera</li>
            <li class="flex gap-4"><span class="font-display text-algeciras-red w-32 shrink-0">07 JUN</span> Aniversario 117 años · revelación campaña</li>
            <li class="flex gap-4"><span class="font-display text-algeciras-red w-32 shrink-0">15 JUN - 5 JUL</span> Renovación socios (objetivo 80% retención)</li>
            <li class="flex gap-4"><span class="font-display text-algeciras-red w-32 shrink-0">6 JUL - 28 AGO</span> Captación nuevos (objetivo 1.500-2.500)</li>
            <li class="flex gap-4"><span class="font-display text-algeciras-red w-32 shrink-0">20-28 AGO</span> Última oportunidad</li>
        </ul>
    </div>
</section>
@endsection

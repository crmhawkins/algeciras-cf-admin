@extends('pages.area-personal._layout')

@section('panel')

<div class="space-y-6">
    <header>
        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">Mis Abonos</p>
        <h2 class="font-display text-3xl md:text-4xl uppercase leading-tight">
            {{ $abonos->count() }} {{ $abonos->count() === 1 ? 'abono' : 'abonos' }} activos
        </h2>
    </header>

    @if($abonos->isEmpty())
        <div class="bg-white border-2 border-algeciras-black/10 p-10 text-center">
            <p class="text-5xl mb-3">🎟️</p>
            <h3 class="font-display text-2xl uppercase mb-2">Aún no tienes abono</h3>
            <p class="text-algeciras-gray text-sm mb-6">Hazte abonado y vive todos los partidos en casa con tu equipo.</p>
            <a href="{{ route('abonos') }}"
               class="inline-block px-6 py-3 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase text-sm shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                Hazte abonado →
            </a>
        </div>
    @else
        <div class="grid gap-5">
            @foreach($abonos as $t)
                @php
                    $productName = $t->product ? $t->product->getTranslation('name','es') : 'Abono';
                    $zoneName    = $t->zone?->name ?? optional($t->product)->zone?->name ?? '—';
                @endphp
                <article x-data="{ confirmar: false }"
                         class="bg-white border-2 border-algeciras-red shadow-brutal flex flex-col md:flex-row overflow-hidden">

                    {{-- Banda lateral roja --}}
                    <div class="md:w-2 bg-algeciras-red"></div>

                    <div class="flex-1 p-5 md:p-6 flex flex-col md:flex-row gap-5 items-center">
                        {{-- QR --}}
                        <div class="w-28 h-28 flex-shrink-0 bg-algeciras-cream grid place-items-center border border-algeciras-black/10">
                            @if($t->qr_image_path)
                                <img src="{{ asset($t->qr_image_path) }}" alt="QR" class="w-full h-full object-contain">
                            @else
                                <span class="font-mono text-[9px] text-algeciras-gray text-center break-all p-2">
                                    {{ \Illuminate\Support\Str::limit($t->uuid, 14) }}
                                </span>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="font-mono text-[10px] tracking-[0.4em] uppercase text-algeciras-red">Abono temporada</p>
                            <h3 class="font-display text-2xl uppercase leading-tight">{{ $productName }}</h3>

                            <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                                <div>
                                    <p class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray">Zona</p>
                                    <p class="font-display">{{ $zoneName }}</p>
                                </div>
                                @if($t->row)
                                    <div>
                                        <p class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray">Fila</p>
                                        <p class="font-display">{{ $t->row }}</p>
                                    </div>
                                @endif
                                @if($t->seat_number)
                                    <div>
                                        <p class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray">Butaca</p>
                                        <p class="font-display">{{ $t->seat_number }}</p>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray">Estado</p>
                                    <p class="font-display text-algeciras-red">{{ ucfirst($t->status ?? 'activo') }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Acciones --}}
                        <div class="flex flex-col gap-2 w-full md:w-auto">
                            <a href="{{ route('area-personal.carnet') }}"
                               class="px-4 py-2 bg-algeciras-black hover:bg-algeciras-red text-white font-display tracking-widest uppercase text-xs text-center transition">
                                Ver QR →
                            </a>

                            <template x-if="!confirmar">
                                <button type="button" @click="confirmar = true"
                                        class="px-4 py-2 border-2 border-algeciras-black/20 hover:border-algeciras-red hover:text-algeciras-red font-display tracking-widest uppercase text-xs transition">
                                    Liberar plaza
                                </button>
                            </template>
                            <template x-if="confirmar">
                                <div class="flex gap-2">
                                    <button type="button"
                                            onclick="alert('Función disponible próximamente. Contacta con el club si necesitas liberar plaza para un partido.')"
                                            class="flex-1 px-3 py-2 bg-algeciras-red text-white font-display tracking-widest uppercase text-xs">
                                        Confirmar
                                    </button>
                                    <button type="button" @click="confirmar = false"
                                            class="px-3 py-2 border-2 border-algeciras-black/20 font-display tracking-widest uppercase text-xs">
                                        ✕
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

@endsection

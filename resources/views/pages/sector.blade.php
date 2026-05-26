@extends('layouts.app')

@section('title', $sector->name)
@section('description', "Selecciona tu butaca en {$sector->name} del Estadio Nuevo Mirador")

@push('head')
<style>
    .seat {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 700;
        font-family: 'Inter', sans-serif;
        color: white;
        cursor: pointer;
        transition: transform 0.15s, background-color 0.15s, box-shadow 0.15s;
        flex-shrink: 0;
    }
    .seat.free        { background: #7fbf3f; }
    .seat.free:hover  { transform: scale(1.18); box-shadow: 0 0 0 3px rgba(127,191,63,0.3); z-index: 5; position: relative; }
    .seat.sold        { background: #CF2E2E; cursor: not-allowed; opacity: 0.7; }
    .seat.reserved    { background: #f59e0b; cursor: not-allowed; opacity: 0.6; }
    .seat.selected    { background: #5a9bd5; transform: scale(1.2); box-shadow: 0 0 0 3px rgba(90,155,213,0.5); }
    .seat-row {
        display: flex;
        gap: 5px;
        justify-content: flex-start;
        margin-bottom: 6px;
    }
    .seat-spacer {
        width: 28px;
        height: 28px;
        flex-shrink: 0;
        display: inline-block;
    }
    @media (max-width: 768px) {
        .seat-spacer { width: 22px; height: 22px; }
    }
    .seat-row-label {
        display: inline-block;
        width: 24px;
        text-align: center;
        font-family: 'Bebas Neue', sans-serif;
        font-size: 14px;
        color: #6b7280;
        margin-right: 8px;
    }
    @media (max-width: 768px) {
        .seat { width: 22px; height: 22px; font-size: 8px; }
        .seat-row { gap: 3px; }
    }
</style>
@endpush

@section('content')

<div x-data="seatPicker({{ $sector->id }}, '{{ $sector->name }}', {{ (float)$sector->price_adult }}, {{ (float)$sector->price_youth }})" class="bg-algeciras-cream">

    {{-- Header con cambiar zona --}}
    <div class="bg-algeciras-black text-white py-6">
        <div class="container mx-auto px-4 lg:px-8 flex items-center justify-between flex-wrap gap-4">
            <a href="{{ route('estadio') }}" class="inline-flex items-center gap-2 px-5 py-3 border-2 border-white hover:bg-white hover:text-algeciras-black transition font-display tracking-widest uppercase text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 12h9m-9 8h13M17 8l4 4-4 4"/></svg>
                Cambiar zona
            </a>
            <div class="text-right">
                <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase">Zona seleccionada</p>
                <h1 class="font-display text-3xl md:text-5xl uppercase">{{ $sector->name }}</h1>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 lg:px-8 py-8 grid lg:grid-cols-5 gap-8">

        {{-- COLUMNA IZQUIERDA: foto + info zona --}}
        <aside class="lg:col-span-2 lg:sticky lg:top-24 lg:self-start space-y-5">
            <div class="relative aspect-[4/3] bg-algeciras-black overflow-hidden">
                <img src="{{ asset('img/club/escudo.png') }}" alt="Vista zona" class="w-full h-full object-contain opacity-30 absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-br from-algeciras-black/60 to-algeciras-red/40"></div>
                <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                    <p class="font-mono text-xs uppercase tracking-[0.3em] text-algeciras-red mb-1">Vista del sector</p>
                    <p class="font-display text-2xl">{{ $sector->zone_label }}</p>
                    <p class="text-sm opacity-80">Sector {{ ucfirst($sector->parity) }} {{ $sector->number }}</p>
                </div>
            </div>

            <div class="bg-white border-2 border-algeciras-black/10 p-5">
                <h2 class="font-display text-xl mb-3">Información</h2>
                <ul class="space-y-2 text-sm">
                    <li class="flex justify-between"><span class="text-algeciras-gray">Sector</span><strong>{{ $sector->name }}</strong></li>
                    <li class="flex justify-between"><span class="text-algeciras-gray">Plazas totales</span><strong>{{ $totalSeats }}</strong></li>
                    <li class="flex justify-between"><span class="text-algeciras-gray">Disponibles</span><strong class="text-algeciras-red">{{ $freeSeats }}</strong></li>
                    <li class="flex justify-between"><span class="text-algeciras-gray">Adulto</span><strong>{{ number_format((float)$sector->price_adult, 2, ',', '.') }}€</strong></li>
                    <li class="flex justify-between"><span class="text-algeciras-gray">Infantil</span><strong>{{ number_format((float)$sector->price_youth, 2, ',', '.') }}€</strong></li>
                </ul>
            </div>

            {{-- Mini-carrito de seats seleccionados --}}
            <div class="bg-algeciras-black text-white p-5" x-show="selected.length > 0" x-cloak x-transition>
                <h3 class="font-display text-xl mb-3">Tu selección (<span x-text="selected.length"></span>)</h3>
                <ul class="space-y-1 mb-4 text-sm max-h-40 overflow-y-auto">
                    <template x-for="s in selected" :key="s.id">
                        <li class="flex justify-between items-center">
                            <span>Fila <span x-text="s.row"></span> · Butaca <span x-text="s.number"></span></span>
                            <button @click="toggle(s)" class="text-algeciras-red hover:text-white text-xs">quitar</button>
                        </li>
                    </template>
                </ul>
                <div class="border-t border-white/20 pt-3 mb-4 flex justify-between items-baseline">
                    <span class="font-display tracking-widest uppercase text-sm">Total</span>
                    <span class="font-display text-3xl text-algeciras-red" x-text="total().toFixed(2).replace('.',',') + '€'"></span>
                </div>
                <button @click="addToCart"
                        class="w-full px-6 py-3 bg-algeciras-red hover:bg-algeciras-red-light transition font-display tracking-widest uppercase text-sm shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none">
                    Añadir al carrito →
                </button>
                <p class="text-[10px] text-white/50 mt-2 text-center">Las butacas se bloquean 10 min mientras compras.</p>
            </div>
        </aside>

        {{-- COLUMNA DERECHA: terreno + grilla butacas --}}
        <div class="lg:col-span-3">

            {{-- TERRENO DE JUEGO label --}}
            <div class="bg-algeciras-black text-white py-8 mb-8 relative overflow-hidden">
                <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
                <h2 class="text-center font-display text-4xl tracking-[0.3em]">Terreno de juego</h2>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-algeciras-red to-transparent"></div>
            </div>

            {{-- Grilla butacas --}}
            @php
                // Estrategia 1:1 con compralaentrada:
                // - Iteramos las seats_row columnas teóricas del sector (0..seats_row-1).
                // - Para cada columna calculamos el número de butaca (initial_seat + col*2).
                // - Si la butaca existe en BD (no oculta): renderiza botón visible.
                // - Si no existe: renderiza spacer invisible (preserva la columna).
                // - Para sectores IMPAR (lado opuesto del estadio), reflejamos el orden
                //   horizontalmente para alinearlos a la derecha como compralaentrada.
                $layoutJson = \Illuminate\Support\Facades\File::get(database_path('data/sectors_layout.json'));
                $layout = collect(json_decode($layoutJson, true))->firstWhere('id', $sector->svg_region);
                $seatsRow = (int) ($layout['seats_row'] ?? $byRow->max(fn($r) => $r->count()));
                $initialSeat = (int) ($layout['initial_seat'] ?? 1);
                $isImpar = $sector->parity === 'impar';
                $rowsList = $byRow->keys()->sort()->values();
            @endphp
            <div class="bg-white border-2 border-algeciras-black/10 p-6 overflow-x-auto">
                @foreach ($rowsList as $row)
                    @php
                        $seatsThisRow = $byRow->get($row)->keyBy('number');
                        // Generamos el array de "celdas" [col0..colN-1]
                        $cells = [];
                        for ($col = 0; $col < $seatsRow; $col++) {
                            $number = $initialSeat + $col * 2;
                            $cells[] = ['number' => $number, 'seat' => $seatsThisRow->get($number)];
                        }
                        // Para IMPAR (lado opuesto del estadio), movemos los spacers
                        // del FINAL al INICIO para que las butacas visibles queden
                        // alineadas a la derecha (espejo de compralaentrada). Los
                        // números siguen ascendentes L→R dentro del bloque visible.
                        if ($isImpar) {
                            $trailingSpacers = 0;
                            for ($i = count($cells) - 1; $i >= 0 && !$cells[$i]['seat']; $i--) {
                                $trailingSpacers++;
                            }
                            if ($trailingSpacers > 0) {
                                $spacers = array_slice($cells, -$trailingSpacers);
                                $rest    = array_slice($cells, 0, -$trailingSpacers);
                                $cells   = array_merge($spacers, $rest);
                            }
                        }
                    @endphp
                    <div class="seat-row flex-nowrap min-w-fit">
                        <span class="seat-row-label">{{ $row }}</span>
                        @foreach ($cells as $cell)
                            @if ($cell['seat'])
                                @php
                                    $seat = $cell['seat'];
                                    $cls = match ($seat->status) {
                                        'free'     => 'free',
                                        'sold'     => 'sold',
                                        'reserved' => 'reserved',
                                        default    => 'blocked',
                                    };
                                @endphp
                                <button
                                    type="button"
                                    class="seat {{ $cls }}"
                                    :class="isSelected({{ $seat->id }}) && 'selected'"
                                    @if ($seat->status === 'free') @click="toggle({ id: {{ $seat->id }}, row: {{ $seat->row }}, number: {{ $seat->number }} })" @endif
                                    title="Fila {{ $seat->row }} · Butaca {{ $seat->number }}"
                                    {{ $seat->status !== 'free' ? 'disabled' : '' }}>
                                    {{ $seat->number }}
                                </button>
                            @else
                                <span class="seat-spacer" aria-hidden="true"></span>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- Leyenda --}}
            <div class="flex justify-center gap-6 mt-8 flex-wrap">
                <div class="flex items-center gap-2"><span class="seat free" style="cursor:default"></span><span class="font-display tracking-widest uppercase text-sm">Disponible</span></div>
                <div class="flex items-center gap-2"><span class="seat sold" style="cursor:default;opacity:1"></span><span class="font-display tracking-widest uppercase text-sm">Ocupado</span></div>
                <div class="flex items-center gap-2"><span class="seat selected" style="cursor:default;transform:none"></span><span class="font-display tracking-widest uppercase text-sm">Seleccionado</span></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function seatPicker(sectorId, sectorName, priceAdult, priceYouth) {
    return {
        sectorId,
        sectorName,
        priceAdult,
        priceYouth,
        selected: [],
        isSelected(id) {
            return this.selected.some(s => s.id === id);
        },
        toggle(seat) {
            const idx = this.selected.findIndex(s => s.id === seat.id);
            if (idx >= 0) this.selected.splice(idx, 1);
            else this.selected.push(seat);
        },
        total() {
            return this.selected.length * this.priceAdult;
        },
        addToCart() {
            if (this.selected.length === 0) return;
            const seats = this.selected.map(s => `Fila ${s.row} · Butaca ${s.number}`).join('\n');
            alert(`Añadidas al carrito ${this.selected.length} butacas de ${this.sectorName}:\n\n${seats}\n\nTotal: ${this.total().toFixed(2).replace('.',',')}€\n\n(Próximamente integración real con CartService.)`);
            this.selected = [];
        },
    };
}
</script>
@endpush

@endsection

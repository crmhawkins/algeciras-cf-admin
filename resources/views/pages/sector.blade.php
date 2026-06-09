@extends('layouts.app')

@section('title', $sector->name)
@section('description', "Selecciona tu butaca en {$sector->name} del Estadio Nuevo Mirador")

@push('head')
@if (request()->query('embed') === 'admin')
<style>
    /* Solo aplica en embed=admin (iframe del modal del panel Filament).
       NO afecta a la web pública (visitas normales a /estadio/sector/X). */
    html, body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    /* Ocultar el chrome de la web publica. */
    header, footer, nav, .grano, .matchday-strip,
    .hero, .hero-section, .sticky, #cookie-banner {
        display: none !important;
    }
    /* Ocultar el header "Cambiar zona" — el operador ya tiene la X del modal. */
    main .bg-algeciras-black.text-white.py-6 { display: none !important; }
    /* Ocultar el sidebar (foto + info + mini carrito + boton "Añadir al carrito").
       En el flujo admin la butaca se elige con un solo click y se manda
       automaticamente al form del panel via postMessage. */
    main aside { display: none !important; }
    /* Ocultar la barra "Terreno de juego" del header de la columna derecha. */
    main .bg-algeciras-black.text-white.py-8 { display: none !important; }
    /* La columna derecha (grilla butacas) pasa a ocupar todo el ancho. */
    main .grid.lg\:grid-cols-5 { display: block !important; }
    main .lg\:col-span-3 { width: 100% !important; max-width: none !important; }
    /* Compactar layout. */
    main, .container, .max-w-7xl, .max-w-6xl, .max-w-5xl {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0.5rem !important;
    }
    /* Anular cualquier opacity:0 que GSAP haya dejado pendiente. */
    [data-fx] { opacity: 1 !important; visibility: visible !important; transform: none !important; }
</style>
@endif
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
            <div class="relative bg-algeciras-black overflow-hidden" style="height: 110px;">
                <img src="{{ asset('img/club/escudo.png') }}" alt="Vista zona" class="w-full h-full object-contain opacity-25 absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-br from-algeciras-black/60 to-algeciras-red/40"></div>
                <div class="absolute inset-0 flex flex-col justify-center p-4 text-white">
                    <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-algeciras-red mb-1">Vista del sector</p>
                    <p class="font-display text-xl leading-tight">{{ $sector->zone_label }}</p>
                    <p class="text-xs opacity-80">Sector {{ ucfirst($sector->parity) }} {{ $sector->number }}</p>
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
                $direction = (int) ($layout['direction'] ?? 1);
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
                        // Para sectores con direction=2 (lado opuesto del estadio),
                        // compralaentrada renderiza las butacas en orden DESCENDENTE
                        // L→R (verificado contra `configuracion` del API live). Invertimos.
                        if ($direction === 2) {
                            $cells = array_reverse($cells);
                        } elseif ($isImpar) {
                            // direction=1 + IMPAR trapezoidal (PREF IMPAR 14, TA IMPAR 9,
                            // TA IMPAR 10): movemos los spacers FINAL al INICIO para que
                            // las visibles queden right-aligned, ascendentes en L→R.
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

            // 2026-06-09: modo embed=admin (panel Filament Renovacion/Abono Nuevo).
            // En cuanto el usuario selecciona 1 butaca, mandar postMessage al
            // window.parent y limpiar selección. Cerrar modal lo hace el padre.
            const isAdminEmbed = new URLSearchParams(window.location.search).get('embed') === 'admin';
            if (isAdminEmbed && this.selected.length > 0) {
                const s = this.selected[0];
                window.parent.postMessage({
                    type: 'algeciras-admin-seat-pick',
                    seat_id: s.id,
                    sector_id: this.sectorId,
                    sector_name: this.sectorName,
                    row: s.row,
                    number: s.number,
                }, '*');
                this.selected = [];
            }
        },
        total() {
            return this.selected.length * this.priceAdult;
        },
        addToCart() {
            if (this.selected.length === 0) return;
            const payload = {
                type: 'cart-add',
                sectorId: this.sectorId,
                sectorName: this.sectorName,
                items: this.selected.map(s => ({ id: s.id, row: s.row, number: s.number, price: this.priceAdult })),
                total: this.total(),
            };

            // Si estamos dentro de la app móvil (WebView), notificar al wrapper nativo
            const isNative = new URLSearchParams(window.location.search).has('native')
                          || (typeof window.ReactNativeWebView !== 'undefined');
            if (isNative && window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage(JSON.stringify(payload));
                this.selected = [];
                return;
            }

            // 2026-06-09: modo `?embed=admin` — embebido como iframe en el panel
            // Filament (Renovación/Abono nuevo). Envía la butaca al window.parent
            // para que rellene los campos sector_id + seat_id del form.
            const isAdminEmbed = new URLSearchParams(window.location.search).get('embed') === 'admin';
            if (isAdminEmbed && this.selected.length > 0) {
                const seat = this.selected[0];
                window.parent.postMessage({
                    type: 'algeciras-admin-seat-pick',
                    seat_id: seat.id,
                    sector_id: this.sectorId,
                    sector_name: this.sectorName,
                    row: seat.row,
                    number: seat.number,
                }, '*');
                this.selected = [];
                return;
            }

            // 2026-06-03: si venimos del flujo de compra (?product=slug en URL)
            // redirigimos a /comprar-directo/{slug} con el primer asiento
            // elegido como seat=ID. Soporta solo 1 butaca por compra por ahora.
            const qs = new URLSearchParams(window.location.search);
            const productSlug = qs.get('product');
            if (productSlug && this.selected.length > 0) {
                const seat = this.selected[0];
                const params = new URLSearchParams({
                    seat: seat.id,
                    row: seat.row,
                    number: seat.number,
                    sector: this.sectorName,
                });
                const matchId = qs.get('match');
                if (matchId) params.set('match', matchId);
                window.location.href = '/comprar-directo/' + encodeURIComponent(productSlug) + '?' + params.toString();
                return;
            }

            // Flujo informativo (visita normal del plano, sin product en URL).
            const seats = this.selected.map(s => `Fila ${s.row} · Butaca ${s.number}`).join('\n');
            alert(`Has elegido ${this.selected.length} butacas de ${this.sectorName}:\n\n${seats}\n\nPara comprarlas accede desde "Hazte abonado" o "Entradas".`);
            this.selected = [];
        },
    };
}
</script>
@endpush

@endsection

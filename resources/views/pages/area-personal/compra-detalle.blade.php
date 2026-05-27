@extends('pages.area-personal._layout')

@section('panel')

<div class="space-y-6">
    <header class="flex flex-wrap items-baseline justify-between gap-3">
        <div>
            <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">
                Pedido <span class="text-algeciras-black">{{ $order->reference }}</span>
            </p>
            <h2 class="font-display text-3xl md:text-4xl uppercase leading-tight">Detalle de la compra</h2>
        </div>
        <a href="{{ route('area-personal.compras') }}"
           class="font-display tracking-widest uppercase text-xs text-algeciras-red hover:underline">
            ← Volver a compras
        </a>
    </header>

    {{-- Resumen pedido --}}
    <div class="bg-white border-2 border-algeciras-black/10 p-6">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray">Fecha</p>
                <p class="font-display text-lg">{{ optional($order->created_at)->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray">Estado</p>
                @php
                    $estadoStyle = match($order->status) {
                        'paid'      => 'background:#16A34A;color:#fff;',
                        'pending'   => 'background:#F59E0B;color:#fff;',
                        'cancelled' => 'background:#71717A;color:#fff;',
                        default     => 'background:#0A0A0A;color:#fff;',
                    };
                @endphp
                <p class="mt-1">
                    <span class="px-2 py-0.5 text-[10px] font-display tracking-widest uppercase" style="{{ $estadoStyle }}">
                        {{ ucfirst($order->status ?? '—') }}
                    </span>
                </p>
            </div>
            <div>
                <p class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray">Canal</p>
                <p class="font-display text-lg">{{ ucfirst($order->channel ?? 'Web') }}</p>
            </div>
            <div>
                <p class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray">Total</p>
                <p class="font-display text-2xl text-algeciras-red">{{ number_format((float) $order->total, 2, ',', '.') }} €</p>
            </div>
        </div>

        @if($order->tracking_carrier || $order->tracking_number)
            <div class="mt-4 pt-4 border-t border-algeciras-black/10 text-sm">
                <p class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray mb-1">Envío</p>
                <p>{{ $order->tracking_carrier ?? '—' }} · <span class="font-mono">{{ $order->tracking_number ?? '—' }}</span></p>
            </div>
        @endif
    </div>

    {{-- Items --}}
    <div class="bg-white border-2 border-algeciras-black/10">
        <header class="px-5 py-3 bg-algeciras-black text-white">
            <h3 class="font-display tracking-widest uppercase text-sm">Artículos</h3>
        </header>
        @foreach($order->items as $item)
            <div class="flex gap-4 p-4 border-b border-algeciras-black/5 last:border-b-0 items-center">
                <div class="w-16 h-16 flex-shrink-0 bg-algeciras-cream grid place-items-center">
                    @if(optional($item->product)->image)
                        <img src="{{ asset($item->product->image) }}" alt="" class="w-full h-full object-contain">
                    @else
                        <span class="text-2xl">📦</span>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-display text-lg leading-tight">{{ $item->name }}</p>
                    @if(!empty($item->meta['size']))
                        <p class="text-xs text-algeciras-gray">Talla {{ $item->meta['size'] }}</p>
                    @endif
                    <p class="text-xs text-algeciras-gray font-mono">SKU {{ $item->sku ?? '—' }}</p>
                </div>
                <div class="text-right text-sm">
                    <p>{{ $item->qty }} × {{ number_format((float) $item->unit_price, 2, ',', '.') }} €</p>
                    <p class="font-display">{{ number_format((float) $item->total, 2, ',', '.') }} €</p>
                </div>
            </div>
        @endforeach

        {{-- Totales --}}
        <div class="px-4 py-3 bg-algeciras-cream text-sm">
            <div class="flex justify-between"><span class="text-algeciras-gray">Subtotal</span><span>{{ number_format((float) $order->subtotal, 2, ',', '.') }} €</span></div>
            <div class="flex justify-between"><span class="text-algeciras-gray">IVA</span><span>{{ number_format((float) $order->vat, 2, ',', '.') }} €</span></div>
            @if((float) $order->shipping_cost > 0)
                <div class="flex justify-between"><span class="text-algeciras-gray">Envío</span><span>{{ number_format((float) $order->shipping_cost, 2, ',', '.') }} €</span></div>
            @endif
            <div class="flex justify-between font-display text-lg mt-2 pt-2 border-t border-algeciras-black/10">
                <span>Total</span><span class="text-algeciras-red">{{ number_format((float) $order->total, 2, ',', '.') }} €</span>
            </div>
        </div>
    </div>

    {{-- Tickets generados --}}
    @if($order->tickets->count() > 0)
        <div class="bg-white border-2 border-algeciras-black/10 p-5">
            <h3 class="font-display tracking-widest uppercase text-sm mb-3">Entradas / Abonos generados</h3>
            <ul class="space-y-2">
                @foreach($order->tickets as $t)
                    <li class="flex items-center justify-between gap-3 p-3 bg-algeciras-cream border-l-4 border-algeciras-red">
                        <div>
                            <p class="font-display">{{ optional($t->product)->getTranslation('name', 'es') ?? 'Ticket' }}</p>
                            <p class="text-xs text-algeciras-gray font-mono">{{ \Illuminate\Support\Str::limit($t->uuid, 24) }}</p>
                        </div>
                        <span class="font-mono text-[10px] tracking-widest uppercase">{{ ucfirst($t->status ?? '—') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Acciones --}}
    <div class="flex flex-wrap gap-3">
        <button type="button" disabled
                class="px-5 py-3 border-2 border-algeciras-black/20 text-algeciras-gray font-display tracking-widest uppercase text-xs cursor-not-allowed"
                title="Próximamente">
            📄 Descargar factura
        </button>
        <a href="{{ route('contacto') }}"
           class="px-5 py-3 border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white font-display tracking-widest uppercase text-xs transition">
            ¿Algún problema?
        </a>
    </div>
</div>

@endsection

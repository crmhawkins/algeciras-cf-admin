@extends('layouts.app')

@section('title', "Pedido {$order->reference}")

@section('content')
<section class="bg-algeciras-black text-white py-12">
    <div class="container mx-auto px-4 lg:px-8">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">✓ Pedido confirmado</p>
        <h1 class="font-display text-5xl md:text-6xl">{{ $order->reference }}</h1>
        <p class="text-algeciras-bone/70 mt-2">Gracias {{ $order->customer->first_name }}. Te hemos enviado el resumen al email <strong class="text-white">{{ $order->customer->email }}</strong>.</p>
    </div>
</section>

<section class="container mx-auto px-4 lg:px-8 py-16 grid lg:grid-cols-3 gap-10">

    <div class="lg:col-span-2 space-y-6">
        {{-- Tickets QR si los hay --}}
        @if ($order->tickets->count())
            <section>
                <h2 class="font-display text-3xl mb-4">Tus entradas y abonos</h2>
                <p class="text-sm text-algeciras-gray mb-6">Llévalos en el móvil o impresos al estadio. Cada QR es de un solo uso, vinculado al DNI del titular.</p>
                <div class="grid md:grid-cols-2 gap-5">
                    @foreach ($order->tickets as $ticket)
                        <article class="bg-white border-2 border-algeciras-red p-5 flex gap-4 items-center clip-tarjeta">
                            @if ($ticket->qr_image_path)
                                <img src="{{ asset($ticket->qr_image_path) }}" alt="QR ticket" class="w-32 h-32 flex-shrink-0">
                            @else
                                <div class="w-32 h-32 bg-algeciras-cream flex items-center justify-center text-xs text-algeciras-gray">QR</div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="font-mono text-xs uppercase tracking-widest text-algeciras-red mb-1">{{ $ticket->product->type === 'abono' ? 'Abono' : 'Entrada' }}</p>
                                <h3 class="font-display text-xl leading-tight">{{ $ticket->product->getTranslation('name','es') }}</h3>
                                @if ($ticket->zone)
                                    <p class="text-sm text-algeciras-gray mt-1">Zona: {{ $ticket->zone->name }}</p>
                                @endif
                                @if ($ticket->holder_name)
                                    <p class="text-xs text-algeciras-gray mt-2">{{ $ticket->holder_name }} · DNI {{ $ticket->holder_dni ?? '—' }}</p>
                                @endif
                                <p class="font-mono text-[10px] text-algeciras-gray/70 mt-2 break-all">{{ Str::limit($ticket->uuid, 24) }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Items pedido --}}
        <section>
            <h2 class="font-display text-3xl mb-4">Resumen del pedido</h2>
            <div class="bg-white border-2 border-algeciras-black/10">
                @foreach ($order->items as $item)
                    <div class="flex gap-4 p-4 border-b border-algeciras-black/5 items-center">
                        <div class="w-16 h-16 flex-shrink-0 bg-algeciras-cream">
                            @if ($item->product->image)
                                <img src="{{ asset($item->product->image) }}" alt="" class="w-full h-full object-contain">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-display text-lg leading-tight">{{ $item->name }}</p>
                            @if ($item->meta['size'] ?? null)
                                <p class="text-xs text-algeciras-gray">Talla: {{ $item->meta['size'] }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="font-display">{{ $item->qty }} × {{ number_format($item->unit_price, 2, ',', '.') }}€</p>
                            <p class="font-display text-lg text-algeciras-red">{{ number_format($item->total, 2, ',', '.') }}€</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Datos de envío --}}
        @if ($order->shipping_address)
            <section class="bg-algeciras-cream p-5 border-l-4 border-algeciras-gold">
                <h3 class="font-display text-xl mb-2">Dirección de envío</h3>
                <p class="text-sm leading-relaxed">
                    <strong>{{ $order->shipping_address['first_name'] }} {{ $order->shipping_address['last_name'] }}</strong><br>
                    {{ $order->shipping_address['address'] }}<br>
                    {{ $order->shipping_address['postal_code'] }} {{ $order->shipping_address['city'] }}
                    @if ($order->shipping_address['province'] ?? null), {{ $order->shipping_address['province'] }} @endif<br>
                    {{ $order->shipping_address['country'] }}
                </p>
            </section>
        @endif
    </div>

    {{-- Totales sidebar --}}
    <aside class="lg:sticky lg:top-24 lg:self-start bg-algeciras-cream p-6 border-l-8 border-algeciras-red">
        <h2 class="font-display text-2xl mb-4">Total cobrado</h2>
        <div class="space-y-1 text-sm text-algeciras-black/85">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($order->subtotal, 2, ',', '.') }}€</span></div>
            <div class="flex justify-between text-algeciras-gray"><span>IVA</span><span>{{ number_format($order->vat, 2, ',', '.') }}€</span></div>
            <div class="flex justify-between text-algeciras-gray"><span>Envío</span><span>{{ number_format($order->shipping_cost, 2, ',', '.') }}€</span></div>
        </div>
        <div class="border-t-2 border-algeciras-black pt-3 mt-3 mb-6 flex justify-between items-baseline">
            <span class="font-display text-xl uppercase">Total</span>
            <span class="font-display text-3xl text-algeciras-red">{{ number_format($order->total, 2, ',', '.') }}€</span>
        </div>
        <p class="text-xs text-algeciras-gray font-mono uppercase tracking-widest">Estado</p>
        <p class="font-display text-2xl text-green-700">PAGADO ✓</p>
        <p class="text-xs text-algeciras-gray mt-1">Método: {{ $order->payment_gateway }} (simulado)</p>

        <a href="{{ route('tienda') }}" class="block mt-6 text-center px-6 py-3 border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white font-display tracking-widest uppercase">Seguir comprando</a>
    </aside>
</section>
@endsection

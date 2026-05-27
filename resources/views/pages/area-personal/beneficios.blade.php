@extends('pages.area-personal._layout')

@php
    $renderCupon = function ($coupon, $pivotStatus = null, $redeemedAt = null) {
        $value = $coupon->display_value ?? '';
        $isUsed = $pivotStatus === 'redeemed';
        return compact('coupon', 'value', 'pivotStatus', 'redeemedAt', 'isUsed');
    };
@endphp

@section('panel')

<div class="space-y-8">
    <header>
        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">Beneficios</p>
        <h2 class="font-display text-3xl md:text-4xl uppercase leading-tight">Tus cupones y ventajas</h2>
    </header>

    {{-- ===================== DISPONIBLES ===================== --}}
    <section>
        <div class="flex items-baseline justify-between mb-4">
            <h3 class="font-display text-2xl uppercase">Disponibles</h3>
            <span class="font-mono text-xs tracking-widest text-algeciras-gray uppercase">
                {{ $disponibles->count() }} {{ $disponibles->count() === 1 ? 'cupón' : 'cupones' }}
            </span>
        </div>

        @if($disponibles->isEmpty() && $sugeridos->isEmpty())
            <div class="bg-white border-2 border-algeciras-black/10 p-8 text-center">
                <p class="text-5xl mb-3">🎁</p>
                <p class="text-algeciras-gray text-sm">No tienes cupones disponibles ahora mismo. Hazte abonado o sigue al club para desbloquear ofertas exclusivas.</p>
            </div>
        @else
            <div class="grid md:grid-cols-2 gap-4">
                {{-- Cupones del cliente --}}
                @foreach($disponibles as $cc)
                    @php $coupon = $cc->coupon; @endphp
                    @if($coupon)
                        <article x-data="{ copied: false }" class="bg-white border-2 border-algeciras-red shadow-brutal p-5 flex flex-col gap-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-mono text-[10px] tracking-[0.4em] uppercase text-algeciras-red">Cupón</p>
                                    <h4 class="font-display text-xl uppercase leading-tight">{{ $coupon->title }}</h4>
                                </div>
                                <span class="font-display text-3xl text-algeciras-red flex-shrink-0">{{ $coupon->display_value }}</span>
                            </div>
                            <p class="text-sm text-algeciras-gray">{{ $coupon->description }}</p>

                            <div class="flex items-center gap-2 mt-2">
                                <code class="flex-1 bg-algeciras-cream px-3 py-2 font-mono text-sm tracking-wider truncate" x-ref="code">{{ $coupon->code }}</code>
                                <button type="button"
                                        @click="navigator.clipboard.writeText($refs.code.innerText.trim()); copied = true; setTimeout(()=>copied=false, 1500)"
                                        class="px-3 py-2 bg-algeciras-black hover:bg-algeciras-red text-white font-display tracking-widest uppercase text-[10px] transition">
                                    <span x-show="!copied">Copiar</span>
                                    <span x-show="copied" x-cloak>✓</span>
                                </button>
                            </div>

                            @if($coupon->valid_until)
                                <p class="text-xs text-algeciras-gray">Válido hasta {{ $coupon->valid_until->format('d/m/Y') }}</p>
                            @endif
                        </article>
                    @endif
                @endforeach

                {{-- Cupones sugeridos por tier (no canjeados aún) --}}
                @foreach($sugeridos as $coupon)
                    <article class="bg-algeciras-cream border-2 border-dashed border-algeciras-black/30 p-5 flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-mono text-[10px] tracking-[0.4em] uppercase text-algeciras-black/50">Para tu tier</p>
                                <h4 class="font-display text-xl uppercase leading-tight">{{ $coupon->title }}</h4>
                            </div>
                            <span class="font-display text-3xl text-algeciras-black/60 flex-shrink-0">{{ $coupon->display_value }}</span>
                        </div>
                        <p class="text-sm text-algeciras-gray">{{ $coupon->description }}</p>
                        <p class="text-xs text-algeciras-gray">
                            Activa tu cupón al hacer tu próxima compra. Código: <code class="font-mono">{{ $coupon->code }}</code>
                        </p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ===================== CANJEADOS ===================== --}}
    @if($canjeados->count() > 0)
        <section>
            <div class="flex items-baseline justify-between mb-4">
                <h3 class="font-display text-2xl uppercase">Canjeados</h3>
                <span class="font-mono text-xs tracking-widest text-algeciras-gray uppercase">
                    {{ $canjeados->count() }} usados
                </span>
            </div>

            <div class="bg-white border-2 border-algeciras-black/10">
                @foreach($canjeados as $cc)
                    @php $coupon = $cc->coupon; @endphp
                    @if($coupon)
                        <div class="flex items-center gap-4 p-4 border-b border-algeciras-black/5 last:border-b-0 opacity-70">
                            <div class="w-12 h-12 flex-shrink-0 grid place-items-center bg-algeciras-cream text-xl">🎟️</div>
                            <div class="flex-1 min-w-0">
                                <p class="font-display truncate">{{ $coupon->title }}</p>
                                <p class="text-xs text-algeciras-gray">
                                    {{ $coupon->display_value }} · Canjeado {{ optional($cc->redeemed_at)->format('d/m/Y') }}
                                </p>
                            </div>
                            <span class="font-mono text-[10px] tracking-widest uppercase text-algeciras-gray">✓ Usado</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif
</div>

@endsection

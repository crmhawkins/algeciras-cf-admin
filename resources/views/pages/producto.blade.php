@extends('layouts.app')

@section('title', $product->getTranslation('name','es'))

@section('content')
<section class="container mx-auto px-4 lg:px-8 py-12">
    <a href="{{ route('tienda') }}" class="font-display tracking-widest uppercase text-sm text-algeciras-red hover:underline">← Volver a la tienda</a>
    <div class="mt-8 grid lg:grid-cols-2 gap-12">
        <div class="bg-algeciras-cream aspect-square flex items-center justify-center">
            @if ($product->image)
                <img src="{{ asset($product->image) }}" alt="{{ $product->getTranslation('name','es') }}" class="w-full h-full object-contain p-10">
            @else
                <span class="font-display text-5xl text-algeciras-red/30">{{ $product->sku }}</span>
            @endif
        </div>
        <div>
            <p class="font-mono uppercase tracking-widest text-algeciras-red text-sm mb-2">{{ $product->type }}{{ $product->category ? ' · '.$product->category->getTranslation('name','es') : '' }}</p>
            <h1 class="font-display text-5xl md:text-6xl mb-6 leading-tight">{{ $product->getTranslation('name','es') }}</h1>

            <div class="flex items-baseline gap-3 mb-6">
                <span class="font-display text-5xl text-algeciras-red">{{ number_format((float)$product->price, 2, ',', '.') }}€</span>
                @if ($product->compare_at_price && $product->compare_at_price > $product->price)
                    <span class="text-xl text-algeciras-gray line-through">{{ number_format((float)$product->compare_at_price, 2, ',', '.') }}€</span>
                @endif
                <span class="text-xs text-algeciras-gray uppercase tracking-widest">IVA {{ $product->vat_rate }}% incl.</span>
            </div>

            <div class="prose max-w-none mb-6 text-algeciras-black/80">
                {!! nl2br(e($product->getTranslation('description','es'))) !!}
            </div>

            @if ($product->type === 'abono' && $product->zone)
                <div class="bg-algeciras-cream p-4 mb-6 border-l-4 border-algeciras-red">
                    <strong class="font-display tracking-widest uppercase">Zona:</strong> {{ $product->zone->name }}
                    @if ($product->season) <span class="text-algeciras-gray">· Temporada {{ $product->season->name }}</span> @endif
                </div>
            @endif

            @if ($product->type === 'entrada' && $product->match)
                <div class="bg-algeciras-cream p-4 mb-6 border-l-4 border-algeciras-red">
                    <strong class="font-display tracking-widest uppercase">Partido:</strong>
                    Algeciras CF vs {{ $product->match->opponent }}
                    <div class="text-sm text-algeciras-gray">{{ $product->match->kickoff_at?->isoFormat('dddd D [de] MMMM, HH:mm[h]') }} · {{ $product->match->stadium }}</div>
                </div>
            @endif

            <livewire:add-to-cart :product="$product" />
            <p class="text-xs text-algeciras-gray mt-3">El checkout Stripe se activa cuando el club nos pase las claves.</p>
        </div>
    </div>
</section>
@endsection

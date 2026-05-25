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

            @if ($product->has_variants && $product->variants->count())
                <div class="mb-6">
                    <p class="font-display tracking-widest uppercase text-sm mb-3">Talla</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($product->variants->where('active', true) as $v)
                            <button class="px-4 py-2 border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white font-display tracking-wider {{ $v->stock <= 0 ? 'opacity-30 line-through cursor-not-allowed' : '' }}">
                                {{ $v->size ?? $v->color }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <button class="w-full md:w-auto px-10 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase text-lg shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition" {{ !$product->is_available ? 'disabled' : '' }}>
                {{ $product->is_available ? 'Añadir al carrito' : 'Agotado' }}
            </button>
            <p class="text-xs text-algeciras-gray mt-3">El carrito y checkout Stripe se activan en fase 2 del proyecto.</p>
        </div>
    </div>
</section>
@endsection

@extends('layouts.app')

@section('title', match($type) { 'abono' => 'Abonos', 'entrada' => 'Entradas', 'merch' => 'Tienda', default => 'Tienda oficial' })

@section('content')
<section class="bg-algeciras-black text-white py-16">
    <div class="container mx-auto px-4 lg:px-8">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">Algeciras CF</p>
        <h1 class="font-display text-6xl md:text-7xl">
            @switch($type)
                @case('abono')   Abonos 2026-27 @break
                @case('entrada') Entradas @break
                @case('merch')   Tienda oficial @break
                @default         Tienda oficial
            @endswitch
        </h1>
    </div>
</section>

<section class="container mx-auto px-4 lg:px-8 py-12">
    <div class="flex flex-wrap gap-2 mb-8 font-display tracking-widest uppercase text-sm">
        <a href="{{ route('tienda') }}"                          class="px-4 py-2 {{ !$type ? 'bg-algeciras-red text-white' : 'border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white' }}">Todos</a>
        <a href="{{ route('tienda', ['type' => 'merch']) }}"     class="px-4 py-2 {{ $type === 'merch' ? 'bg-algeciras-red text-white' : 'border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white' }}">Merch</a>
        <a href="{{ route('tienda', ['type' => 'abono']) }}"     class="px-4 py-2 {{ $type === 'abono' ? 'bg-algeciras-red text-white' : 'border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white' }}">Abonos</a>
        <a href="{{ route('tienda', ['type' => 'entrada']) }}"   class="px-4 py-2 {{ $type === 'entrada' ? 'bg-algeciras-red text-white' : 'border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white' }}">Entradas</a>
    </div>

    @if ($products->count())
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ($products as $p)
                <a href="{{ route('producto', $p->slug) }}" class="group block bg-white border-2 border-transparent hover:border-algeciras-red transition">
                    <div class="aspect-square bg-gradient-to-br from-algeciras-cream to-algeciras-bone flex items-center justify-center overflow-hidden">
                        @if ($p->image)
                            <img src="{{ asset($p->image) }}" alt="{{ $p->getTranslation('name','es') }}" class="w-full h-full object-contain p-6 group-hover:scale-105 transition">
                        @else
                            <span class="font-display text-3xl text-algeciras-red/30 px-4 text-center">{{ $p->sku }}</span>
                        @endif
                    </div>
                    <div class="p-4">
                        <div class="flex items-center justify-between text-xs font-mono uppercase tracking-widest text-algeciras-red mb-1">
                            <span>{{ $p->type }}</span>
                            @if ($p->compare_at_price && $p->compare_at_price > $p->price)
                                <span class="bg-algeciras-red text-white px-2 py-0.5">OFERTA</span>
                            @endif
                        </div>
                        <h3 class="font-display text-lg leading-tight mb-2">{{ $p->getTranslation('name','es') }}</h3>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display text-2xl text-algeciras-red">{{ number_format((float)$p->price, 2, ',', '.') }}€</span>
                            @if ($p->compare_at_price && $p->compare_at_price > $p->price)
                                <span class="text-sm text-algeciras-gray line-through">{{ number_format((float)$p->compare_at_price, 2, ',', '.') }}€</span>
                            @endif
                        </div>
                        @if ($p->capacity && $p->remaining !== null)
                            <p class="text-xs text-algeciras-gray mt-1">{{ $p->remaining }} disponibles</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <p class="text-algeciras-gray py-16 text-center">No hay productos en esta categoría.</p>
    @endif
</section>
@endsection

@extends('layouts.app')

@section('title', 'Actualidad')

@section('content')
<section class="bg-algeciras-black text-white py-16">
    <div class="container mx-auto px-4 lg:px-8">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">Algeciras CF</p>
        <h1 class="font-display text-6xl md:text-7xl">Actualidad</h1>
    </div>
</section>

<section class="container mx-auto px-4 lg:px-8 py-16">
    @if ($news->count())
        <div class="grid md:grid-cols-3 gap-6">
            @foreach ($news as $n)
                <a href="{{ route('noticia', $n->slug) }}" class="group block bg-white border-2 border-transparent hover:border-algeciras-red transition">
                    <div class="aspect-[4/3] bg-gradient-to-br from-algeciras-red/30 to-algeciras-black overflow-hidden">
                        @if ($n->cover_image)
                            <img src="{{ asset($n->cover_image) }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition">
                        @endif
                    </div>
                    <div class="p-5">
                        <p class="font-mono text-xs uppercase tracking-widest text-algeciras-red mb-2">{{ $n->published_at?->isoFormat('D MMM YYYY') }}</p>
                        <h2 class="font-display text-xl leading-tight">{{ $n->getTranslation('title','es') }}</h2>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-10">{{ $news->links() }}</div>
    @else
        <div class="bg-algeciras-cream border-2 border-algeciras-red p-8 text-center">
            <p class="font-display text-2xl mb-3">Aún no hay noticias publicadas</p>
            <p class="text-algeciras-gray">El blog de la web actual contenía spam SEO (casinos), por lo que partimos de cero. Las primeras noticias reales se publicarán desde el admin en cuanto el club las apruebe.</p>
        </div>
    @endif
</section>
@endsection

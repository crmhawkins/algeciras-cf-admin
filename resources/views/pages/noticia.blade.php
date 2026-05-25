@extends('layouts.app')

@section('title', $news->getTranslation('title','es'))

@section('content')
<article class="container mx-auto px-4 lg:px-8 py-12 max-w-4xl">
    <a href="{{ route('actualidad') }}" class="font-display tracking-widest uppercase text-sm text-algeciras-red hover:underline">← Actualidad</a>

    <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mt-6 mb-2">
        {{ $news->category }} · {{ $news->published_at?->isoFormat('D MMM YYYY') }}
    </p>
    <h1 class="font-display text-5xl md:text-6xl leading-tight mb-6">{{ $news->getTranslation('title','es') }}</h1>

    @if ($news->cover_image)
        <img src="{{ asset($news->cover_image) }}" alt="" class="w-full mb-8">
    @endif

    <div class="prose max-w-none text-algeciras-black/85 text-lg leading-relaxed">
        {!! nl2br(e($news->getTranslation('body','es'))) !!}
    </div>

    @if ($news->author)
        <div class="mt-10 pt-6 border-t border-algeciras-black/10 text-sm text-algeciras-gray">
            Publicado por {{ $news->author->name }}
        </div>
    @endif
</article>
@endsection

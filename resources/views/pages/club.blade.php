@extends('layouts.app')

@section('title', 'El Club')

@section('content')
<section class="bg-algeciras-black text-white py-20 relative overflow-hidden">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <div class="container mx-auto px-4 lg:px-8 relative z-10">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">Desde 1912</p>
        <h1 class="font-display text-6xl md:text-8xl leading-none">El Club</h1>
    </div>
</section>

<section class="container mx-auto px-4 lg:px-8 py-16 grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 prose max-w-none text-algeciras-black/85 text-lg leading-relaxed">
        <h2 class="font-display text-4xl mb-4 not-prose">Más de un siglo defendiendo el escudo</h2>
        <p>El Algeciras Club de Fútbol nace ligado a la llegada del ferrocarril y la influencia británica en la Bahía. Lo que empezó como un juego importado se convirtió en seña de identidad de la ciudad y de toda la comarca del Campo de Gibraltar.</p>
        <p>Con <strong>9 temporadas en Segunda División A</strong> y <strong>18 en Segunda B</strong> en su palmarés, el club ha vivido todas las categorías del fútbol español. El estadio <strong>Nuevo Mirador</strong> es la casa donde la afición sigue empujando partido tras partido.</p>
        <p>La temporada <strong>2026-27</strong> arranca en <strong>Primera RFEF</strong> con un proyecto digital 360 que une web, tienda online, app móvil, redes y contenido bajo una sola marca.</p>
    </div>
    <aside class="space-y-6">
        <div class="bg-algeciras-cream p-6 border-l-4 border-algeciras-red">
            <h3 class="font-display text-2xl mb-3">Datos del club</h3>
            <ul class="space-y-2 text-sm">
                <li><strong class="text-algeciras-red">Fundación:</strong> 1912</li>
                <li><strong class="text-algeciras-red">Estadio:</strong> Nuevo Mirador</li>
                <li><strong class="text-algeciras-red">Ciudad:</strong> Algeciras, Cádiz</li>
                <li><strong class="text-algeciras-red">Categoría 26-27:</strong> Primera RFEF</li>
                <li><strong class="text-algeciras-red">Marca técnica:</strong> Capelli Sport (hasta 2030)</li>
            </ul>
        </div>
        <div class="bg-algeciras-black text-white p-6">
            <h3 class="font-display text-2xl mb-3">Palmarés</h3>
            <ul class="space-y-2 text-sm text-algeciras-bone/80">
                <li>9 temporadas en Segunda División A</li>
                <li>18 temporadas en Segunda División B</li>
                <li>Trofeo Ciudad de El Puerto</li>
                <li>Trofeo Ciudad de La Línea</li>
                <li>Trofeo Virgen de La Palma</li>
            </ul>
        </div>
    </aside>
</section>

<section class="bg-algeciras-cream py-16">
    <div class="container mx-auto px-4 lg:px-8">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">Estructura</p>
        <h2 class="font-display text-5xl mb-10">Quién hace el club</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($staff as $s)
                <article class="bg-white p-5 border-l-4 border-algeciras-red">
                    <h3 class="font-display text-xl">{{ $s->name }}</h3>
                    <p class="text-sm text-algeciras-gray uppercase tracking-widest">{{ $s->role }}</p>
                    @if ($s->email)
                        <a href="mailto:{{ $s->email }}" class="text-sm text-algeciras-red hover:underline mt-2 inline-block">{{ $s->email }}</a>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endsection

@extends('layouts.app')

@section('title', 'Área Personal — Socios Algeciras CF')
@section('description', 'Accede a tu área personal de socio del Algeciras CF: tus abonos, entradas, pedidos y datos del club.')

@section('content')

{{-- HERO --}}
<section class="relative bg-algeciras-black text-white overflow-hidden py-16 lg:py-20">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <div data-fx="hero-layer" data-speed="0.4"
         class="absolute -bottom-32 left-0 right-0 h-64 bg-algeciras-red transform -skew-y-3 origin-left opacity-90"></div>
    <div class="relative container mx-auto px-4 lg:px-8 z-10" data-fx="hero-text">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-4">Área de socio</p>
        <h1 class="font-display text-6xl md:text-8xl lg:text-[10rem] leading-[0.85] tracking-tight">Tu Algeciras</h1>
        <p class="mt-6 text-lg text-algeciras-bone/80 max-w-2xl">
            Accede a tus abonos, entradas digitales, pedidos y datos del club.
            <strong class="text-algeciras-red">Solo para socios.</strong>
        </p>
    </div>
</section>

<section class="bg-algeciras-cream py-16 lg:py-24">
    <div class="container mx-auto px-4 lg:px-8 grid lg:grid-cols-2 gap-12 lg:gap-20 items-start">

        {{-- COLUMNA IZQUIERDA: Formulario login --}}
        <div class="bg-white border-2 border-algeciras-black/10 shadow-brutal p-8 lg:p-10" data-fx="reveal">
            <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-2">Iniciar sesión</p>
            <h2 class="font-display text-4xl lg:text-5xl mb-8">Accede a tu cuenta</h2>

            <form action="#" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block font-display tracking-widest uppercase text-xs mb-2">Email o número de socio</label>
                    <input type="text" name="email" id="email" required
                           class="w-full px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono"
                           placeholder="socio@ejemplo.com">
                </div>
                <div>
                    <label for="password" class="block font-display tracking-widest uppercase text-xs mb-2">Contraseña</label>
                    <input type="password" name="password" id="password" required
                           class="w-full px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono"
                           placeholder="••••••••">
                </div>

                <div class="flex justify-between items-center text-sm">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="accent-algeciras-red">
                        <span class="text-algeciras-gray">Mantener sesión</span>
                    </label>
                    <a href="#" class="text-algeciras-red hover:underline font-display tracking-wider uppercase text-xs">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" disabled
                        class="w-full px-6 py-4 bg-algeciras-red text-white font-display tracking-widest uppercase shadow-brutal opacity-60 cursor-not-allowed">
                    Entrar →
                </button>

                <div class="text-center mt-4 p-4 bg-algeciras-cream border-l-4 border-algeciras-red">
                    <p class="text-sm text-algeciras-black/70">
                        <strong>🚧 Área en construcción.</strong><br>
                        El acceso para socios estará operativo en los próximos días.
                        Mientras tanto, hazte abonado o consulta el calendario.
                    </p>
                </div>
            </form>

            <hr class="my-8 border-algeciras-black/10">
            <p class="text-center text-sm text-algeciras-gray">
                ¿Aún no eres socio?
                <a href="{{ route('abonos') }}" class="text-algeciras-red font-display tracking-wider uppercase text-xs ml-1 hover:underline">Hazte abonado →</a>
            </p>
        </div>

        {{-- COLUMNA DERECHA: Qué podrás hacer --}}
        <div class="space-y-6" data-fx="reveal-stagger">
            <div>
                <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-2">Lo que encontrarás aquí</p>
                <h2 class="font-display text-4xl lg:text-5xl leading-tight">Todo tu Algeciras<br>en un click.</h2>
            </div>

            @php
                $features = [
                    ['icon' => '🎫', 'title' => 'Tus abonos', 'text' => 'Visualiza y descarga tus abonos digitales para cada temporada.'],
                    ['icon' => '🏟️', 'title' => 'Entradas', 'text' => 'Histórico de entradas compradas y acceso directo al QR para entrar al estadio.'],
                    ['icon' => '📦', 'title' => 'Pedidos tienda', 'text' => 'Tus compras de equipación y merch, estado de envío y descargas de facturas.'],
                    ['icon' => '⚙️', 'title' => 'Datos personales', 'text' => 'Edita tu información de contacto, dirección y preferencias de comunicación.'],
                ];
            @endphp

            @foreach ($features as $f)
                <div class="flex gap-5 p-5 bg-white border-2 border-algeciras-black/10 hover:border-algeciras-red transition">
                    <div class="text-4xl flex-shrink-0">{{ $f['icon'] }}</div>
                    <div>
                        <h3 class="font-display text-2xl mb-1">{{ $f['title'] }}</h3>
                        <p class="text-sm text-algeciras-gray">{{ $f['text'] }}</p>
                    </div>
                </div>
            @endforeach

            <div class="bg-algeciras-black text-white p-6">
                <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-2">¿Necesitas ayuda?</p>
                <p class="mb-4">Si tienes problemas con el acceso o no recuerdas tu número de socio, contacta con el club.</p>
                <a href="{{ route('contacto') }}" class="inline-block px-5 py-3 border-2 border-white hover:bg-white hover:text-algeciras-black transition font-display tracking-widest uppercase text-sm">
                    Contactar →
                </a>
            </div>
        </div>

    </div>
</section>

@endsection

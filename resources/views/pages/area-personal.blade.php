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

        {{-- COLUMNA IZQUIERDA: Tabs Login / Registro --}}
        <div x-data="{ tab: '{{ session('register_attempted') || ($errors->any() && old('name')) ? 'register' : 'login' }}' }"
             class="bg-white border-2 border-algeciras-black/10 shadow-brutal p-8 lg:p-10" data-fx="reveal">

            {{-- Tabs --}}
            <div class="flex gap-0 mb-8 border-b-2 border-algeciras-black/10">
                <button type="button" @click="tab = 'login'"
                        :class="tab === 'login' ? 'border-algeciras-red text-algeciras-black' : 'border-transparent text-algeciras-gray hover:text-algeciras-black'"
                        class="flex-1 py-3 font-display tracking-widest uppercase text-sm border-b-4 transition">
                    Iniciar sesión
                </button>
                <button type="button" @click="tab = 'register'"
                        :class="tab === 'register' ? 'border-algeciras-red text-algeciras-black' : 'border-transparent text-algeciras-gray hover:text-algeciras-black'"
                        class="flex-1 py-3 font-display tracking-widest uppercase text-sm border-b-4 transition">
                    Crear cuenta
                </button>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-4 bg-algeciras-red/10 border-l-4 border-algeciras-red">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-algeciras-red font-medium">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- LOGIN FORM --}}
            <form x-show="tab === 'login'" action="{{ route('area-personal.login') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block font-display tracking-widest uppercase text-xs mb-2">Email</label>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}"
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

                <button type="submit"
                        class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                    Entrar →
                </button>

                <p class="text-center text-sm text-algeciras-gray pt-2">
                    ¿No tienes cuenta?
                    <button type="button" @click="tab = 'register'" class="text-algeciras-red font-display tracking-wider uppercase text-xs ml-1 hover:underline">
                        Crear cuenta gratis
                    </button>
                </p>
            </form>

            {{-- REGISTER FORM (mismas campos que la app móvil) --}}
            <form x-show="tab === 'register'" x-cloak action="{{ route('area-personal.register') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-display tracking-widest uppercase text-xs mb-2">Nombre *</label>
                        <input type="text" name="first_name" required value="{{ old('first_name') }}"
                               autocomplete="given-name"
                               class="w-full px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono"
                               placeholder="Ivan">
                    </div>
                    <div>
                        <label class="block font-display tracking-widest uppercase text-xs mb-2">Apellidos</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}"
                               autocomplete="family-name"
                               class="w-full px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono"
                               placeholder="García López">
                    </div>
                </div>

                <div>
                    <label class="block font-display tracking-widest uppercase text-xs mb-2">Email *</label>
                    <input type="email" name="email" required value="{{ old('email') }}"
                           autocomplete="email"
                           class="w-full px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono"
                           placeholder="tu@email.com">
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-display tracking-widest uppercase text-xs mb-2">Teléfono</label>
                        <div class="flex">
                            <span class="px-3 py-3 border-2 border-r-0 border-algeciras-black/10 bg-algeciras-cream font-mono text-sm">🇪🇸 +34</span>
                            <input type="tel" name="phone" value="{{ old('phone') }}"
                                   autocomplete="tel-national"
                                   class="flex-1 px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono"
                                   placeholder="600 000 000">
                        </div>
                    </div>
                    <div>
                        <label class="block font-display tracking-widest uppercase text-xs mb-2">DNI / NIE</label>
                        <input type="text" name="dni" value="{{ old('dni') }}"
                               class="w-full px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono uppercase"
                               placeholder="12345678X">
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-display tracking-widest uppercase text-xs mb-2">Contraseña *</label>
                        <input type="password" name="password" required minlength="6"
                               autocomplete="new-password"
                               class="w-full px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono"
                               placeholder="••••••••">
                    </div>
                    <div>
                        <label class="block font-display tracking-widest uppercase text-xs mb-2">Confirmar *</label>
                        <input type="password" name="password_confirmation" required minlength="6"
                               autocomplete="new-password"
                               class="w-full px-4 py-3 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono"
                               placeholder="••••••••">
                    </div>
                </div>

                <label class="flex items-start gap-2 text-xs text-algeciras-gray cursor-pointer">
                    <input type="checkbox" required class="mt-1 accent-algeciras-red">
                    <span>
                        Acepto la <a href="{{ route('privacidad') }}" target="_blank" class="text-algeciras-red hover:underline">Política de Privacidad</a>
                        y los términos del club.
                    </span>
                </label>

                <button type="submit"
                        class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                    Crear cuenta →
                </button>

                <p class="text-center text-sm text-algeciras-gray pt-2">
                    ¿Ya tienes cuenta?
                    <button type="button" @click="tab = 'login'" class="text-algeciras-red font-display tracking-wider uppercase text-xs ml-1 hover:underline">
                        Inicia sesión
                    </button>
                </p>
            </form>
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

@extends('layouts.app')

@section('title', 'Mi área personal — '.$user->name)
@section('description', 'Tu cuenta del Algeciras CF — abonos, entradas y datos personales.')

@section('content')

<section class="relative bg-algeciras-black text-white overflow-hidden py-12">
    <div class="absolute inset-0 grano opacity-30 pointer-events-none"></div>
    <div data-fx="hero-layer" data-speed="0.4"
         class="absolute -bottom-32 left-0 right-0 h-48 bg-algeciras-red transform -skew-y-3 origin-left opacity-90"></div>
    <div class="relative container mx-auto px-4 lg:px-8 z-10 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-2">Tu área</p>
            <h1 class="font-display text-4xl md:text-6xl uppercase">Hola, {{ explode(' ', $user->name)[0] }}</h1>
            <p class="mt-2 text-algeciras-bone/80">
                {{ $user->email }}
                @if($customer?->is_socio)
                    <span class="ml-2 inline-block px-2 py-1 bg-algeciras-red text-xs uppercase tracking-widest">Socio nº {{ $customer->socio_number ?? '—' }}</span>
                @endif
            </p>
        </div>
        <form action="{{ route('area-personal.logout') }}" method="POST">
            @csrf
            <button type="submit" class="px-5 py-3 border-2 border-white hover:bg-white hover:text-algeciras-black transition font-display tracking-widest uppercase text-sm">
                Cerrar sesión →
            </button>
        </form>
    </div>
</section>

<section class="bg-algeciras-cream py-12">
    <div class="container mx-auto px-4 lg:px-8 grid lg:grid-cols-12 gap-8">

        <aside class="lg:col-span-3 space-y-2" data-fx="reveal">
            <a href="#abonos" class="block px-5 py-3 bg-white border-2 border-algeciras-black/10 hover:border-algeciras-red transition font-display tracking-wider uppercase text-sm">
                🎫 Mis Abonos ({{ $abonos->count() }})
            </a>
            <a href="#entradas" class="block px-5 py-3 bg-white border-2 border-algeciras-black/10 hover:border-algeciras-red transition font-display tracking-wider uppercase text-sm">
                🏟️ Mis Entradas ({{ $entradas->count() }})
            </a>
            <a href="#datos" class="block px-5 py-3 bg-white border-2 border-algeciras-black/10 hover:border-algeciras-red transition font-display tracking-wider uppercase text-sm">
                ⚙️ Mis Datos
            </a>
            <a href="{{ route('zona-socio') }}" class="block px-5 py-3 bg-algeciras-black text-white hover:bg-algeciras-red transition font-display tracking-wider uppercase text-sm">
                ⭐ Zona Socio
            </a>
        </aside>

        <div class="lg:col-span-9 space-y-10" data-fx="reveal-stagger">

            {{-- Abonos --}}
            <div id="abonos" class="bg-white border-2 border-algeciras-black/10 p-8">
                <h2 class="font-display text-3xl mb-6">Tus abonos</h2>
                @if($abonos->isEmpty())
                    <p class="text-algeciras-gray">Aún no tienes abonos. <a href="{{ route('abonos') }}" class="text-algeciras-red font-display tracking-wider uppercase text-xs hover:underline">Hazte abonado →</a></p>
                @else
                    <ul class="space-y-3">
                        @foreach($abonos as $t)
                            <li class="flex flex-wrap justify-between items-center p-4 bg-algeciras-cream border-l-4 border-algeciras-red">
                                <div>
                                    <strong class="font-display tracking-wider uppercase">{{ $t->product?->name ?? 'Abono' }}</strong>
                                    @if($t->row && $t->seat_number)
                                        <p class="text-sm text-algeciras-gray">Fila {{ $t->row }} · Butaca {{ $t->seat_number }}</p>
                                    @endif
                                </div>
                                <a href="#" class="px-4 py-2 bg-algeciras-black text-white text-xs font-display tracking-wider uppercase hover:bg-algeciras-red transition">Ver QR →</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Entradas --}}
            <div id="entradas" class="bg-white border-2 border-algeciras-black/10 p-8">
                <h2 class="font-display text-3xl mb-6">Tus entradas</h2>
                @if($entradas->isEmpty())
                    <p class="text-algeciras-gray">No tienes entradas registradas. <a href="{{ route('tienda', ['type' => 'entrada']) }}" class="text-algeciras-red font-display tracking-wider uppercase text-xs hover:underline">Comprar entradas →</a></p>
                @else
                    <ul class="space-y-3">
                        @foreach($entradas as $t)
                            <li class="flex flex-wrap justify-between items-center p-4 bg-algeciras-cream border-l-4 border-algeciras-red">
                                <div>
                                    <strong class="font-display tracking-wider uppercase">{{ $t->product?->name ?? 'Entrada' }}</strong>
                                    @if($t->row && $t->seat_number)
                                        <p class="text-sm text-algeciras-gray">Fila {{ $t->row }} · Butaca {{ $t->seat_number }}</p>
                                    @endif
                                </div>
                                <a href="#" class="px-4 py-2 bg-algeciras-black text-white text-xs font-display tracking-wider uppercase hover:bg-algeciras-red transition">Ver QR →</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Datos personales --}}
            <div id="datos" class="bg-white border-2 border-algeciras-black/10 p-8">
                <h2 class="font-display text-3xl mb-6">Mis datos</h2>
                <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                    <div class="flex justify-between border-b border-algeciras-black/10 pb-2">
                        <dt class="text-algeciras-gray">Nombre</dt><dd><strong>{{ $user->name }}</strong></dd>
                    </div>
                    <div class="flex justify-between border-b border-algeciras-black/10 pb-2">
                        <dt class="text-algeciras-gray">Email</dt><dd><strong>{{ $user->email }}</strong></dd>
                    </div>
                    @if($customer)
                        <div class="flex justify-between border-b border-algeciras-black/10 pb-2">
                            <dt class="text-algeciras-gray">Teléfono</dt><dd><strong>{{ $customer->phone ?? '—' }}</strong></dd>
                        </div>
                        <div class="flex justify-between border-b border-algeciras-black/10 pb-2">
                            <dt class="text-algeciras-gray">DNI</dt><dd><strong>{{ $customer->dni ?? '—' }}</strong></dd>
                        </div>
                        <div class="flex justify-between border-b border-algeciras-black/10 pb-2">
                            <dt class="text-algeciras-gray">Ciudad</dt><dd><strong>{{ $customer->city ?? '—' }}</strong></dd>
                        </div>
                        <div class="flex justify-between border-b border-algeciras-black/10 pb-2">
                            <dt class="text-algeciras-gray">CP</dt><dd><strong>{{ $customer->postal_code ?? '—' }}</strong></dd>
                        </div>
                    @endif
                </dl>
                <p class="mt-6 text-xs text-algeciras-gray">Para modificar tus datos, contacta con el club en <a href="{{ route('contacto') }}" class="text-algeciras-red hover:underline">contacto</a>.</p>
            </div>

        </div>
    </div>
</section>

@endsection

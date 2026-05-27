{{--
    Layout interno del Área Personal — estilo "Mi Cuenta" de club grande
    (Real Madrid / FC Barcelona).

    Sidebar sticky con carnet digital compacto + nav. Main panel a la
    derecha donde cada sub-vista pinta su contenido via @section('panel').

    Variables esperadas:
      - $user (App\Models\User)
      - $customer (App\Models\Customer|null)
      - $count_abonos, $count_entradas, $count_compras, $count_cupones (ints)
--}}
@extends('layouts.app')

@section('title', 'Mi Cuenta — Algeciras CF')
@section('description', 'Tu área personal del Algeciras CF: carnet digital, abonos, entradas, compras y beneficios.')

@php
    $firstName = $customer?->first_name ?: explode(' ', (string) $user->name)[0];
    $fullName  = $customer
        ? trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''))
        : $user->name;
    if ($fullName === '') $fullName = $user->name;

    $tier      = $customer?->tier ?? 'aficionado';
    $tierLabel = $customer?->tier_label ?? 'Aficionado';

    // Estilo del badge del tier (inline para evitar purga de JIT)
    $tierBadgeStyle = match ($tier) {
        'abonado_vip' => 'background:#D4AF37;color:#0A0A0A;',
        'abonado'     => 'background:#CF2E2E;color:#FFFFFF;',
        'peñista'     => 'background:#6B21A8;color:#FFFFFF;',
        default       => 'background:#3F3F46;color:#FFFFFF;',
    };

    $socioNumero = $customer?->socio_number ?: str_pad((string) ($user->id ?? 0), 6, '0', STR_PAD_LEFT);

    // Payload del QR del carnet — uuid (socio_number) + hash auth (no se firma con app key aquí
    // para mantenerlo simple; el QR real lo genera la API)
    $qrPayload = "ACF:".$socioNumero.":".substr(sha1((string) $user->id . $user->email), 0, 12);

    // Generar QR con endroid si está disponible, si no SVG placeholder
    try {
        if (class_exists(\Endroid\QrCode\Builder\Builder::class)) {
            $qrResult = \Endroid\QrCode\Builder\Builder::create()
                ->writer(new \Endroid\QrCode\Writer\SvgWriter())
                ->data($qrPayload)
                ->size(220)
                ->margin(0)
                ->build();
            $qrSvg = $qrResult->getString();
        } else {
            $qrSvg = null;
        }
    } catch (\Throwable $e) {
        $qrSvg = null;
    }

    // Items del nav
    $navItems = [
        ['route' => 'area-personal.resumen',        'label' => 'Resumen',         'count' => null,                'icon' => 'home'],
        ['route' => 'area-personal.carnet',         'label' => 'Mi carnet',       'count' => null,                'icon' => 'card'],
        ['route' => 'area-personal.abonos',         'label' => 'Mis Abonos',      'count' => $count_abonos ?? 0,  'icon' => 'ticket'],
        ['route' => 'area-personal.entradas',       'label' => 'Mis Entradas',    'count' => $count_entradas ?? 0,'icon' => 'qr'],
        ['route' => 'area-personal.compras',        'label' => 'Mis Compras',     'count' => $count_compras ?? 0, 'icon' => 'bag'],
        ['route' => 'area-personal.beneficios',     'label' => 'Beneficios',      'count' => $count_cupones ?? 0, 'icon' => 'gift'],
        ['route' => 'area-personal.actividad',      'label' => 'Mi Actividad',    'count' => null,                'icon' => 'pulse'],
        ['route' => 'area-personal.datos',          'label' => 'Mis Datos',       'count' => null,                'icon' => 'user'],
        ['route' => 'area-personal.notificaciones', 'label' => 'Notificaciones',  'count' => null,                'icon' => 'bell'],
    ];

    $icons = [
        'home'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 11l9-8 9 8M5 10v10h14V10"/>',
        'card'   => '<rect x="2" y="5" width="20" height="14" rx="2" stroke-linejoin="round"/><path d="M2 10h20" stroke-linecap="round"/><path d="M6 15h4" stroke-linecap="round"/>',
        'ticket' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8a2 2 0 012-2h14a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2a2 2 0 100-4V8z"/><path stroke-linecap="round" d="M12 6v12" stroke-dasharray="2 2"/>',
        'qr'     => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM18 18h3v3h-3z"/>',
        'bag'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14l-1 13H6L5 7zM9 7V5a3 3 0 016 0v2"/>',
        'gift'   => '<rect x="3" y="8" width="18" height="13" rx="1"/><path d="M3 12h18" stroke-linecap="round"/><path d="M12 8v13" stroke-linecap="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2 0-4-1-4-3a2 2 0 014-1c0 2-2 4 0 4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c2 0 4-1 4-3a2 2 0 00-4-1c0 2 2 4 0 4z"/>',
        'pulse'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l3-8 4 16 3-8h4"/>',
        'user'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'bell'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
    ];
@endphp

@section('content')

{{-- Barra superior del área personal (saludo + breadcrumb + logout) --}}
<section class="bg-algeciras-black text-white border-b-2 border-algeciras-red">
    <div class="container mx-auto px-4 lg:px-8 py-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">Mi Cuenta</p>
            <h1 class="font-display text-2xl md:text-3xl uppercase">Hola, {{ $firstName }}</h1>
        </div>
        <form action="{{ route('area-personal.logout') }}" method="POST">
            @csrf
            <button type="submit"
                    class="px-4 py-2 border-2 border-white/30 hover:border-white hover:bg-white hover:text-algeciras-black transition font-display tracking-widest uppercase text-xs">
                Cerrar sesión →
            </button>
        </form>
    </div>
</section>

<section class="bg-algeciras-cream min-h-[70vh] py-8 lg:py-10">
    <div class="container mx-auto px-4 lg:px-8 grid lg:grid-cols-12 gap-8">

        {{-- ============================================================
             SIDEBAR sticky
             ============================================================ --}}
        <aside class="lg:col-span-3" style="align-self: flex-start;">
            <div class="lg:sticky" style="top: 6rem;">

                {{-- Carnet digital compacto --}}
                <div class="relative overflow-hidden shadow-brutal border-2 border-algeciras-black"
                     style="aspect-ratio: 16/10; background: linear-gradient(135deg, #0A0A0A 0%, #1A1A1A 50%, #CF2E2E 130%);">

                    {{-- Escudo translúcido de fondo --}}
                    <img src="{{ asset('img/club/escudo.png') }}" alt=""
                         class="absolute -right-8 -bottom-10 h-44 w-auto opacity-10 pointer-events-none">

                    <div class="relative h-full p-4 flex flex-col justify-between text-white">
                        <div class="flex items-start justify-between gap-3">
                            {{-- Foto / avatar --}}
                            <div class="w-12 h-12 rounded-full border-2 border-white/40 bg-white/10 flex items-center justify-center text-lg font-display flex-shrink-0">
                                {{ mb_strtoupper(mb_substr($firstName, 0, 1)) }}
                            </div>
                            <div class="text-right">
                                <p class="font-mono text-[9px] tracking-[0.3em] uppercase text-white/60">Socio Nº</p>
                                <p class="font-mono text-base font-bold leading-tight">{{ $socioNumero }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="font-display text-sm uppercase leading-tight tracking-wide truncate">{{ $fullName }}</p>
                            <div class="mt-1 flex items-end justify-between gap-2">
                                <span class="inline-block px-2 py-0.5 text-[9px] font-display tracking-[0.2em] uppercase"
                                      style="{{ $tierBadgeStyle }}">
                                    {{ $tierLabel }}
                                </span>
                                {{-- QR pequeño --}}
                                <div class="bg-white p-1" style="width:42px;height:42px;">
                                    @if($qrSvg)
                                        <div style="width:34px;height:34px;">{!! preg_replace('/width="[^"]*"\s*height="[^"]*"/', 'width="34" height="34"', $qrSvg) !!}</div>
                                    @else
                                        {{-- SVG placeholder simulando QR --}}
                                        <svg viewBox="0 0 21 21" style="width:34px;height:34px;" xmlns="http://www.w3.org/2000/svg" fill="#000">
                                            <rect x="0" y="0" width="7" height="7" fill="#000"/>
                                            <rect x="1" y="1" width="5" height="5" fill="#fff"/>
                                            <rect x="2" y="2" width="3" height="3" fill="#000"/>
                                            <rect x="14" y="0" width="7" height="7" fill="#000"/>
                                            <rect x="15" y="1" width="5" height="5" fill="#fff"/>
                                            <rect x="16" y="2" width="3" height="3" fill="#000"/>
                                            <rect x="0" y="14" width="7" height="7" fill="#000"/>
                                            <rect x="1" y="15" width="5" height="5" fill="#fff"/>
                                            <rect x="2" y="16" width="3" height="3" fill="#000"/>
                                            <rect x="9" y="9" width="3" height="3"/>
                                            <rect x="13" y="9" width="2" height="2"/>
                                            <rect x="9" y="13" width="2" height="2"/>
                                            <rect x="11" y="11" width="2" height="2"/>
                                            <rect x="15" y="13" width="2" height="2"/>
                                            <rect x="13" y="15" width="2" height="2"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Nav vertical --}}
                <nav class="mt-5 bg-white border-2 border-algeciras-black/10 divide-y divide-algeciras-black/5">
                    @foreach ($navItems as $item)
                        @php $isActive = request()->routeIs($item['route']); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center justify-between gap-3 px-4 py-3 font-display tracking-wider uppercase text-xs transition group"
                           style="{{ $isActive ? 'background:#CF2E2E;color:#FFFFFF;' : 'color:#0A0A0A;' }}">
                            <span class="flex items-center gap-3 min-w-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2">
                                    {!! $icons[$item['icon']] ?? '' !!}
                                </svg>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </span>
                            @if(!is_null($item['count']))
                                <span class="inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 text-[10px] font-mono font-bold rounded-full"
                                      style="{{ $isActive ? 'background:#FFFFFF;color:#CF2E2E;' : 'background:#0A0A0A;color:#FFFFFF;' }}">
                                    {{ $item['count'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach

                    {{-- Cerrar sesión --}}
                    <form action="{{ route('area-personal.logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-3 px-4 py-3 font-display tracking-wider uppercase text-xs hover:bg-algeciras-black hover:text-white transition text-left"
                                style="color:#0A0A0A;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9m-2 8H5a2 2 0 01-2-2V6a2 2 0 012-2h2"/>
                            </svg>
                            Cerrar sesión
                        </button>
                    </form>
                </nav>

                {{-- CTA Zona Socio --}}
                <a href="{{ route('zona-socio') }}"
                   class="mt-4 block px-4 py-3 bg-algeciras-black text-white text-center font-display tracking-widest uppercase text-xs hover:bg-algeciras-red transition">
                    ⭐ Zona Socio →
                </a>
            </div>
        </aside>

        {{-- ============================================================
             MAIN
             ============================================================ --}}
        <div class="lg:col-span-9 min-w-0">

            @if(session('status'))
                <div class="mb-5 p-4 bg-green-50 border-l-4 border-green-600 text-green-800 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 p-4 bg-algeciras-red/10 border-l-4 border-algeciras-red">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-algeciras-red font-medium">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @yield('panel')
        </div>
    </div>
</section>
@endsection

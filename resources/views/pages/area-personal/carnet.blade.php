@extends('pages.area-personal._layout')

@php
    $firstName = $customer?->first_name ?: explode(' ', (string) $user->name)[0];
    $fullName  = $customer
        ? trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''))
        : $user->name;
    if ($fullName === '') $fullName = $user->name;

    $tier      = $customer?->tier ?? 'aficionado';
    $tierLabel = $customer?->tier_label ?? 'Aficionado';

    $tierBadgeStyle = match ($tier) {
        'abonado_vip' => 'background:#D4AF37;color:#0A0A0A;',
        'abonado'     => 'background:#CF2E2E;color:#FFFFFF;',
        'peñista'     => 'background:#6B21A8;color:#FFFFFF;',
        default       => 'background:#3F3F46;color:#FFFFFF;',
    };

    $socioNumero = $customer?->socio_number ?: str_pad((string) ($user->id ?? 0), 6, '0', STR_PAD_LEFT);
    $qrPayload   = "ACF:".$socioNumero.":".substr(sha1((string) $user->id . $user->email), 0, 12);

    $qrSvgBig = null;
    try {
        if (class_exists(\Endroid\QrCode\Builder\Builder::class)) {
            $qrResult = \Endroid\QrCode\Builder\Builder::create()
                ->writer(new \Endroid\QrCode\Writer\SvgWriter())
                ->data($qrPayload)
                ->size(420)
                ->margin(2)
                ->build();
            $qrSvgBig = $qrResult->getString();
        }
    } catch (\Throwable $e) {
        $qrSvgBig = null;
    }
@endphp

@section('panel')

<div class="space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">Mi carnet digital</p>
            <h2 class="font-display text-3xl md:text-4xl uppercase leading-tight">Tu acceso al estadio</h2>
        </div>
        <button type="button" disabled
                class="px-5 py-3 border-2 border-algeciras-black/30 text-algeciras-gray font-display tracking-widest uppercase text-xs cursor-not-allowed"
                title="Próximamente">
            🪪 Añadir a Wallet
        </button>
    </header>

    {{-- Carnet grande --}}
    <div class="relative overflow-hidden shadow-brutal border-2 border-algeciras-black mx-auto w-full"
         style="aspect-ratio: 16/10; max-width: 720px; background: linear-gradient(135deg, #0A0A0A 0%, #1A1A1A 50%, #CF2E2E 140%);">

        {{-- Escudo translúcido --}}
        <img src="{{ asset('img/club/escudo.png') }}" alt=""
             class="absolute -left-12 -bottom-16 h-[110%] w-auto opacity-10 pointer-events-none">

        {{-- Cinta diagonal roja decorativa --}}
        <div class="absolute -right-20 top-10 w-72 h-2 bg-algeciras-red rotate-45 opacity-60 pointer-events-none"></div>

        <div class="relative h-full p-6 md:p-8 flex flex-col justify-between text-white">

            {{-- Cabecera --}}
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="font-mono text-[10px] md:text-xs tracking-[0.4em] uppercase text-algeciras-red">Algeciras C.F.</p>
                    <p class="font-display text-base md:text-lg tracking-widest uppercase mt-1">Temporada {{ env('CLUB_SEASON', '2026-27') }}</p>
                </div>
                <span class="inline-block px-3 py-1 text-xs font-display tracking-[0.3em] uppercase"
                      style="{{ $tierBadgeStyle }}">
                    {{ $tierLabel }}
                </span>
            </div>

            {{-- Centro: nombre + QR --}}
            <div class="flex items-end justify-between gap-6">
                <div class="min-w-0 flex-1">
                    <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-white/60">Titular</p>
                    <p class="font-display text-2xl md:text-3xl uppercase leading-tight truncate">{{ $fullName }}</p>

                    <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-white/60 mt-4">Socio Nº</p>
                    <p class="font-mono text-3xl md:text-5xl font-bold leading-none">{{ $socioNumero }}</p>
                </div>

                {{-- QR grande --}}
                <div class="bg-white p-2 flex-shrink-0" style="width:160px;height:160px;">
                    @if($qrSvgBig)
                        <div style="width:144px;height:144px;">{!! preg_replace('/width="[^"]*"\s*height="[^"]*"/', 'width="144" height="144"', $qrSvgBig) !!}</div>
                    @else
                        <div class="w-full h-full grid place-items-center text-xs text-algeciras-gray font-mono">
                            {{ $qrPayload }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Info acceso --}}
    <div class="grid md:grid-cols-3 gap-4">
        <div class="bg-white border-2 border-algeciras-black/10 p-5">
            <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Estado</p>
            <p class="font-display text-2xl mt-2 text-algeciras-red">Activo</p>
        </div>
        <div class="bg-white border-2 border-algeciras-black/10 p-5">
            <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Válido hasta</p>
            <p class="font-display text-2xl mt-2">30 jun 2027</p>
        </div>
        <div class="bg-white border-2 border-algeciras-black/10 p-5">
            <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Estadio</p>
            <p class="font-display text-2xl mt-2">Nuevo Mirador</p>
        </div>
    </div>

    <p class="text-xs text-algeciras-gray text-center">
        Presenta este QR en el acceso del estadio. No lo compartas — es personal e intransferible.
    </p>
</div>

@endsection

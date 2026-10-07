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

    // QR del carnet = el QR REAL del primer abono ACTIVO del socio. Es
    // estático toda la temporada (payload v1, ABONO|v1|ticket|customer|season)
    // → el club puede imprimirlo en plástico y vale para los 19 partidos.
    // Si el socio aún no tiene abono, mostramos un mensaje en su lugar.
    $abonoTicket = null;
    if ($customer) {
        $abonoTicket = \App\Models\Ticket::where('customer_id', $customer->id)
            ->where('status', 'issued')
            ->whereHas('product', fn ($q) => $q->where('type', 'abono'))
            ->orderByDesc('id')
            ->first();

        // Si el ticket existe pero NO se generó el PNG (caso de tickets
        // viejos antes del fix), lo generamos ahora — el método es
        // idempotente: si ya existía, lo regenera con el mismo payload v1.
        if ($abonoTicket && empty($abonoTicket->qr_image_path)) {
            try {
                app(\App\Services\QrService::class)->generate($abonoTicket);
                $abonoTicket->refresh();
            } catch (\Throwable $e) {
                \Log::warning('QR generate fallo en carnet', ['err' => $e->getMessage()]);
            }
        }
    }
    $qrUrl = $abonoTicket?->qr_image_path
        ? \Illuminate\Support\Facades\Storage::url($abonoTicket->qr_image_path)
        : null;

    // Foto de perfil del socio (subible desde el form de abajo).
    $photoUrl = $user->profile_image
        ? \Illuminate\Support\Facades\Storage::url($user->profile_image)
        : null;
    // Iniciales como placeholder si no tiene foto.
    $iniciales = strtoupper(substr($firstName, 0, 1)
        . substr($customer?->last_name ?? $user->name, 0, 1));
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

        {{-- Escudo translúcido decorativo CENTRADO en todo el carnet
             (petición del cliente). Z-0 para que la foto/texto/QR se
             pinten encima. Opacidad 0.30. --}}
        <img src="{{ asset('img/club/escudo.png') }}" alt=""
             class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 h-[95%] w-auto opacity-30 pointer-events-none z-0">

        {{-- Cinta diagonal roja decorativa --}}
        <div class="absolute -right-20 top-10 w-72 h-2 bg-algeciras-red rotate-45 opacity-60 pointer-events-none"></div>

        <div class="relative z-10 h-full p-6 md:p-8 text-white flex gap-6">

            {{-- COLUMNA IZQUIERDA: Cabecera + FOTO GRANDE del socio --}}
            <div class="flex flex-col items-start gap-4 flex-shrink-0">
                <div>
                    <p class="font-mono text-[10px] md:text-xs tracking-[0.4em] uppercase text-algeciras-red">Algeciras C.F.</p>
                    <p class="font-display text-base md:text-lg tracking-widest uppercase mt-1">Temporada {{ env('CLUB_SEASON', '2026-27') }}</p>
                </div>

                {{-- Foto grande circular debajo del título (petición del cliente). --}}
                @if($photoUrl)
                    <div class="w-40 h-40 md:w-52 md:h-52 rounded-full overflow-hidden border-4 border-algeciras-red bg-white shadow-xl">
                        <img src="{{ $photoUrl }}" alt="{{ $fullName }}" class="w-full h-full object-cover">
                    </div>
                @else
                    <div class="w-40 h-40 md:w-52 md:h-52 rounded-full border-4 border-algeciras-red bg-algeciras-black/60 grid place-items-center font-display text-6xl text-white/80 shadow-xl">
                        {{ $iniciales ?: '?' }}
                    </div>
                @endif
            </div>

            {{-- COLUMNA DERECHA: badge + nombre + nº socio + QR --}}
            <div class="flex-1 flex flex-col justify-between min-w-0">
                {{-- Badge ABONADO en la esquina superior derecha --}}
                <div class="flex justify-end">
                    <span class="inline-block px-3 py-1 text-xs font-display tracking-[0.3em] uppercase"
                          style="{{ $tierBadgeStyle }}">
                        {{ $tierLabel }}
                    </span>
                </div>

                {{-- Nombre + socio + QR en la parte inferior --}}
                <div class="flex items-end justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-white/60">Titular</p>
                        <p class="font-display text-2xl md:text-3xl uppercase leading-tight truncate">{{ $fullName }}</p>

                        <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-white/60 mt-4">Socio Nº</p>
                        <p class="font-mono text-3xl md:text-5xl font-bold leading-none">{{ $socioNumero }}</p>
                    </div>

                    {{-- QR del abono — estático toda la temporada. --}}
                    <div class="bg-white p-2 flex-shrink-0" style="width:140px;height:140px;">
                        @if($qrUrl)
                            <img src="{{ $qrUrl }}" alt="QR Abono {{ $socioNumero }}"
                                 class="w-full h-full object-contain">
                        @else
                            <div class="w-full h-full grid place-items-center text-center text-[10px] text-algeciras-gray font-mono px-2 leading-tight">
                                Tu QR aparecerá cuando seas abonado de la temporada.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Foto de perfil — upload / eliminar --}}
    <div class="bg-white border-2 border-algeciras-black/10 p-5">
        <div class="flex items-center justify-between mb-3 flex-wrap gap-3">
            <div>
                <p class="font-mono text-[10px] tracking-[0.3em] uppercase text-algeciras-gray">Foto del carnet</p>
                <p class="font-display text-xl mt-1">Tu foto en el carnet digital</p>
            </div>
            @if (session('status'))
                <span class="font-mono uppercase tracking-widest text-xs text-algeciras-red">{{ session('status') }}</span>
            @endif
        </div>

        @if ($errors->any())
            <div class="bg-algeciras-red text-white p-3 mb-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('area-personal.foto.upload') }}" enctype="multipart/form-data"
              class="flex items-center gap-4 flex-wrap">
            @csrf
            <div class="flex items-center gap-3">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="" class="w-20 h-20 rounded-full object-cover border-2 border-algeciras-black/20">
                @else
                    <div class="w-20 h-20 rounded-full bg-algeciras-cream grid place-items-center font-display text-2xl text-algeciras-gray">
                        {{ $iniciales ?: '?' }}
                    </div>
                @endif
                <div class="text-xs text-algeciras-gray">
                    JPG, PNG o WEBP<br>Máx. 5 MB<br>
                    Mejor cuadrada (1:1) para que no se recorte
                </div>
            </div>
            <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" required
                   class="text-sm font-mono ml-auto">
            <div class="flex gap-2">
                <button type="submit"
                        class="px-5 py-2 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase text-xs shadow-brutal">
                    Subir foto
                </button>
            </div>
        </form>

        @if($photoUrl)
            <form method="POST" action="{{ route('area-personal.foto.delete') }}" class="mt-3">
                @csrf
                <button type="submit"
                        class="text-xs font-mono uppercase tracking-widest text-algeciras-gray hover:text-algeciras-red"
                        onclick="return confirm('¿Eliminar tu foto del carnet?');">
                    Eliminar foto actual
                </button>
            </form>
        @endif
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

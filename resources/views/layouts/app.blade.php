<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#E30A2C">
    <meta name="description" content="@yield('description', 'Web oficial del Algeciras Club de Fútbol. Tienda, entradas, abonos y noticias de la temporada 2026-27. #CrecemosContigo')">

    <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('img/escudo.svg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=bebas-neue:400|inter:400,500,600,700,800,900|anton:400">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">

    {{-- ========== HEADER ========== --}}
    <header class="sticky top-0 z-50 bg-algeciras-black text-white border-b-2 border-algeciras-red">
        <div class="container mx-auto px-4 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <img src="{{ asset('img/escudo.svg') }}" alt="Escudo Algeciras CF" class="h-10 w-auto transition group-hover:scale-110">
                <div class="leading-none">
                    <div class="font-display text-xl tracking-wider">Algeciras</div>
                    <div class="font-display text-[10px] text-algeciras-red tracking-widest">CLUB DE FÚTBOL</div>
                </div>
            </a>

            <nav class="hidden lg:flex items-center gap-8 font-display tracking-wider">
                <a href="#" class="hover:text-algeciras-red transition uppercase">Club</a>
                <a href="#" class="hover:text-algeciras-red transition uppercase">Equipo</a>
                <a href="#" class="hover:text-algeciras-red transition uppercase">Calendario</a>
                <a href="#" class="hover:text-algeciras-red transition uppercase">Actualidad</a>
                <a href="#" class="hover:text-algeciras-red transition uppercase">Tienda</a>
                <a href="#" class="hover:text-algeciras-red transition uppercase">Entradas</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="#" class="hidden md:inline-block px-4 py-2 bg-algeciras-red hover:bg-algeciras-red-dark transition font-display tracking-wider uppercase text-sm">
                    Hazte abonado
                </a>
                <button class="lg:hidden" aria-label="Menú">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
    </header>

    {{-- ========== CONTENT ========== --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- ========== FOOTER ========== --}}
    <footer class="bg-algeciras-black text-algeciras-bone mt-24">
        <div class="container mx-auto px-4 lg:px-8 py-16 grid md:grid-cols-4 gap-10">
            <div>
                <img src="{{ asset('img/escudo.svg') }}" alt="Algeciras CF" class="h-16 w-auto mb-4">
                <p class="text-sm text-algeciras-bone/70">Algeciras Club de Fútbol — Primera RFEF</p>
                <p class="text-sm text-algeciras-red font-display tracking-widest mt-2">{{ env('CLUB_HASHTAG') }}</p>
            </div>
            <div>
                <h4 class="font-display text-algeciras-red text-lg tracking-wider mb-3">Club</h4>
                <ul class="space-y-2 text-sm text-algeciras-bone/80">
                    <li><a href="#" class="hover:text-white">Historia</a></li>
                    <li><a href="#" class="hover:text-white">Plantilla</a></li>
                    <li><a href="#" class="hover:text-white">Calendario</a></li>
                    <li><a href="#" class="hover:text-white">Clasificación</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-display text-algeciras-red text-lg tracking-wider mb-3">Tienda</h4>
                <ul class="space-y-2 text-sm text-algeciras-bone/80">
                    <li><a href="#" class="hover:text-white">Equipación 26-27</a></li>
                    <li><a href="#" class="hover:text-white">Lifestyle</a></li>
                    <li><a href="#" class="hover:text-white">Abonos</a></li>
                    <li><a href="#" class="hover:text-white">Entradas</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-display text-algeciras-red text-lg tracking-wider mb-3">Contacto</h4>
                <ul class="space-y-2 text-sm text-algeciras-bone/80">
                    <li>Estadio Nuevo Mirador</li>
                    <li>Algeciras (Cádiz)</li>
                    <li><a href="mailto:info@algecirasclubdefutbol.com" class="hover:text-white">info@algecirasclubdefutbol.com</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10 py-4">
            <div class="container mx-auto px-4 lg:px-8 flex flex-col md:flex-row justify-between gap-2 text-xs text-algeciras-bone/50">
                <span>© {{ date('Y') }} Algeciras Club de Fútbol. Todos los derechos reservados.</span>
                <span>Temporada {{ env('CLUB_SEASON') }} · Primera Federación</span>
            </div>
        </div>
    </footer>

</body>
</html>

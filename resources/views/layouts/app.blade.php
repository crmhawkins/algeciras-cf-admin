<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#E30A2C">
    <meta name="description" content="@yield('description', 'Web oficial del Algeciras Club de Fútbol. Tienda, entradas, abonos y noticias de la temporada 2026-27. #CrecemosContigo')">

    <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('img/club/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/club/escudo.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=anybody:400,500,600,700,800,900|inter:400,500,600,700,800,900|bebas-neue:400">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">

    {{-- ========== HEADER ========== --}}
    <header class="sticky top-0 z-50 bg-algeciras-black text-white border-b-2 border-algeciras-red">
        <div class="container mx-auto px-4 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <img src="{{ asset('img/club/escudo.png') }}" alt="Escudo Algeciras CF" class="h-12 w-auto transition group-hover:scale-110">
                <div class="leading-none hidden sm:block">
                    <div class="font-display font-black text-xl tracking-wider uppercase">Algeciras</div>
                    <div class="font-display text-[10px] text-algeciras-red tracking-[0.3em] uppercase">Club de Fútbol</div>
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
                <img src="{{ asset('img/club/escudo.png') }}" alt="Algeciras CF" class="h-20 w-auto mb-4">
                <p class="text-sm text-algeciras-bone/70 leading-relaxed">Algeciras Club de Fútbol<br>Primera Federación · Temporada {{ env('CLUB_SEASON') }}</p>
                <p class="text-sm text-algeciras-red font-display tracking-widest mt-3 uppercase">{{ env('CLUB_HASHTAG') }}</p>
                <div class="flex gap-3 mt-4">
                    <a href="https://www.instagram.com/algecirascf/" target="_blank" rel="noopener" class="hover:text-algeciras-red transition" aria-label="Instagram">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 5.77.13 4.9.33 4.14.63c-.79.31-1.46.72-2.13 1.38C1.35 2.68.94 3.35.63 4.14.33 4.9.13 5.77.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.28.26 2.15.56 2.91.31.79.72 1.46 1.38 2.13.67.67 1.34 1.07 2.13 1.38.76.3 1.63.5 2.91.56 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c1.28-.06 2.15-.26 2.91-.56.79-.31 1.46-.72 2.13-1.38.67-.67 1.07-1.34 1.38-2.13.3-.76.5-1.63.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.28-.26-2.15-.56-2.91-.31-.79-.72-1.46-1.38-2.13C21.32 1.35 20.65.94 19.86.63 19.1.33 18.23.13 16.95.07 15.67.01 15.26 0 12 0zm0 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84zM12 16a4 4 0 1 1 4-4 4 4 0 0 1-4 4zm6.41-11.85a1.44 1.44 0 1 0 1.44 1.44 1.44 1.44 0 0 0-1.44-1.44z"/></svg>
                    </a>
                    <a href="https://twitter.com/AlgecirasCF" target="_blank" rel="noopener" class="hover:text-algeciras-red transition" aria-label="X / Twitter">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://www.youtube.com/@Algeciras_cf" target="_blank" rel="noopener" class="hover:text-algeciras-red transition" aria-label="YouTube">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                </div>
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

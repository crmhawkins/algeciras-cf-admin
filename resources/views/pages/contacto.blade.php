@extends('layouts.app')

@section('title', 'Contacto')

@section('content')
<section class="bg-algeciras-black text-white py-16">
    <div class="container mx-auto px-4 lg:px-8">
        <p class="font-mono text-algeciras-red text-sm tracking-[0.4em] uppercase mb-2">Estamos aquí</p>
        <h1 class="font-display text-6xl md:text-7xl">Contacto</h1>
    </div>
</section>

<section class="container mx-auto px-4 lg:px-8 py-16 grid md:grid-cols-2 gap-12">
    <div>
        <h2 class="font-display text-3xl mb-6">Estadio Nuevo Mirador</h2>
        <p class="mb-8 text-algeciras-black/85">Algeciras (Cádiz). Casa del Algeciras Club de Fútbol desde hace más de un siglo.</p>

        <div class="space-y-4 text-sm">
            <div>
                <p class="font-mono uppercase tracking-widest text-algeciras-red text-xs mb-1">Email general</p>
                <a href="mailto:info@algecirasclubdefutbol.com" class="font-display text-lg hover:text-algeciras-red">info@algecirasclubdefutbol.com</a>
            </div>
            <div>
                <p class="font-mono uppercase tracking-widest text-algeciras-red text-xs mb-1">Ticketing</p>
                <a href="mailto:s.ternero@algecirasclubdefutbol.com" class="font-display text-lg hover:text-algeciras-red">s.ternero@algecirasclubdefutbol.com</a>
            </div>
            <div>
                <p class="font-mono uppercase tracking-widest text-algeciras-red text-xs mb-1">Academia / Cantera</p>
                <a href="mailto:cantera@algecirasclubdefutbol.com" class="font-display text-lg hover:text-algeciras-red">cantera@algecirasclubdefutbol.com</a>
            </div>
            <div>
                <p class="font-mono uppercase tracking-widest text-algeciras-red text-xs mb-1">Prensa</p>
                <a href="mailto:protocolo@algecirasclubdefutbol.com" class="font-display text-lg hover:text-algeciras-red">protocolo@algecirasclubdefutbol.com</a>
            </div>
        </div>

        <div class="mt-10">
            <p class="font-mono uppercase tracking-widest text-algeciras-red text-xs mb-3">Síguenos</p>
            <div class="flex gap-3">
                <a href="https://www.instagram.com/algecirascf/" target="_blank" rel="noopener" class="px-4 py-2 bg-algeciras-black text-white hover:bg-algeciras-red font-display tracking-widest text-sm uppercase">Instagram</a>
                <a href="https://twitter.com/AlgecirasCF"      target="_blank" rel="noopener" class="px-4 py-2 bg-algeciras-black text-white hover:bg-algeciras-red font-display tracking-widest text-sm uppercase">X</a>
                <a href="https://www.youtube.com/@Algeciras_cf" target="_blank" rel="noopener" class="px-4 py-2 bg-algeciras-black text-white hover:bg-algeciras-red font-display tracking-widest text-sm uppercase">YouTube</a>
            </div>
        </div>
    </div>

    <div class="bg-algeciras-cream p-8 border-l-8 border-algeciras-red">
        <h2 class="font-display text-3xl mb-6">Escríbenos</h2>
        <form class="space-y-4">
            <input type="text"  placeholder="Nombre y apellidos" class="w-full px-4 py-3 bg-white border-2 border-algeciras-black/10 focus:border-algeciras-red outline-none">
            <input type="email" placeholder="Email"              class="w-full px-4 py-3 bg-white border-2 border-algeciras-black/10 focus:border-algeciras-red outline-none">
            <input type="text"  placeholder="Asunto"             class="w-full px-4 py-3 bg-white border-2 border-algeciras-black/10 focus:border-algeciras-red outline-none">
            <textarea rows="5"  placeholder="Mensaje"            class="w-full px-4 py-3 bg-white border-2 border-algeciras-black/10 focus:border-algeciras-red outline-none"></textarea>
            <button type="button" class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase">Enviar</button>
            <p class="text-xs text-algeciras-gray">El formulario se conectará al CRM del club en fase 2.</p>
        </form>
    </div>
</section>
@endsection

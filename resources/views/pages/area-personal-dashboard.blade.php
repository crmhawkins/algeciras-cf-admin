{{--
    LEGACY: vista antigua del área personal monolítica.
    Sustituida por el nuevo layout `pages/area-personal/_layout.blade.php`
    con sub-vistas (resumen, carnet, abonos, entradas, compras, beneficios,
    actividad, datos, notificaciones).

    Esta vista se conserva por seguridad — si algún código legacy intenta
    renderizarla, redirige al resumen dentro del nuevo flujo.
--}}
@extends('pages.area-personal._layout')

@section('panel')
    @php
        $count_abonos    = $count_abonos    ?? ($abonos->count()   ?? 0);
        $count_entradas  = $count_entradas  ?? ($entradas->count() ?? 0);
        $count_compras   = $count_compras   ?? 0;
        $count_cupones   = $count_cupones   ?? 0;
    @endphp

    <div class="bg-white border-2 border-algeciras-red shadow-brutal p-8 text-center">
        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-2">Área renovada</p>
        <h2 class="font-display text-3xl uppercase mb-3">Hemos rediseñado tu cuenta</h2>
        <p class="text-algeciras-gray text-sm mb-6">
            Ahora tienes carnet digital, abonos, entradas, beneficios y mucho más en secciones independientes.
        </p>
        <a href="{{ route('area-personal.resumen') }}"
           class="inline-block px-6 py-3 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase text-xs shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
            Ir al resumen →
        </a>
    </div>
@endsection

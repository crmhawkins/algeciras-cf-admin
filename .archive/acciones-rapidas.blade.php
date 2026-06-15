{{-- Atajos grandes para taquilla / oficina. Cada botón abre Cobro Manual
     con el modo + tipo de producto pre-seleccionado. --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">⚡ Acciones rápidas</x-slot>
        <x-slot name="description">Atajos para registrar ventas presenciales sin pensar.</x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">

            {{-- 🔁 Renovación abono --}}
            <a href="{{ url('/admin/cobro-manual?modo=existente&tipo=abono') }}"
               class="group relative flex flex-col items-center justify-center text-center
                      p-5 rounded-xl border-2 border-amber-400 bg-amber-50
                      hover:bg-amber-100 hover:border-amber-500 hover:shadow-lg
                      transition-all duration-150 cursor-pointer">
                <div class="text-4xl mb-2">🔁</div>
                <div class="font-bold text-amber-900">Renovación abono</div>
                <div class="text-xs text-amber-700 mt-1">Cliente con número de socio</div>
            </a>

            {{-- ✨ Abono nuevo --}}
            <a href="{{ url('/admin/cobro-manual?modo=nuevo&tipo=abono') }}"
               class="group relative flex flex-col items-center justify-center text-center
                      p-5 rounded-xl border-2 border-emerald-400 bg-emerald-50
                      hover:bg-emerald-100 hover:border-emerald-500 hover:shadow-lg
                      transition-all duration-150 cursor-pointer">
                <div class="text-4xl mb-2">✨</div>
                <div class="font-bold text-emerald-900">Abono nuevo</div>
                <div class="text-xs text-emerald-700 mt-1">Alta de nuevo abonado</div>
            </a>

            {{-- 🎫 Cobrar entrada --}}
            <a href="{{ url('/admin/cobro-manual?tipo=entrada') }}"
               class="group relative flex flex-col items-center justify-center text-center
                      p-5 rounded-xl border-2 border-sky-400 bg-sky-50
                      hover:bg-sky-100 hover:border-sky-500 hover:shadow-lg
                      transition-all duration-150 cursor-pointer">
                <div class="text-4xl mb-2">🎫</div>
                <div class="font-bold text-sky-900">Cobrar entrada</div>
                <div class="text-xs text-sky-700 mt-1">Entrada de partido suelto</div>
            </a>

            {{-- 🛍️ Producto tienda --}}
            <a href="{{ url('/admin/cobro-manual?tipo=merch') }}"
               class="group relative flex flex-col items-center justify-center text-center
                      p-5 rounded-xl border-2 border-zinc-300 bg-zinc-50
                      hover:bg-zinc-100 hover:border-zinc-400 hover:shadow-lg
                      transition-all duration-150 cursor-pointer">
                <div class="text-4xl mb-2">🛍️</div>
                <div class="font-bold text-zinc-900">Producto tienda</div>
                <div class="text-xs text-zinc-700 mt-1">Bufanda, camiseta, merchandising</div>
            </a>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>

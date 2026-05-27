@extends('pages.area-personal._layout')

@section('panel')

<div class="space-y-6">
    <header>
        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">Notificaciones</p>
        <h2 class="font-display text-3xl md:text-4xl uppercase leading-tight">Cómo quieres saber del club</h2>
        <p class="text-sm text-algeciras-gray mt-2">
            Elige por categoría si quieres recibir avisos por <strong>email</strong>, <strong>push</strong> (app móvil) o ambos.
        </p>
    </header>

    <form action="{{ route('area-personal.notificaciones.update') }}" method="POST"
          class="bg-white border-2 border-algeciras-black/10 overflow-hidden">
        @csrf

        <header class="hidden md:grid grid-cols-12 gap-4 px-5 py-3 bg-algeciras-black text-white text-xs font-display tracking-widest uppercase">
            <div class="col-span-8">Categoría</div>
            <div class="col-span-2 text-center">Email</div>
            <div class="col-span-2 text-center">Push</div>
        </header>

        <ul>
            @foreach($categories as $key => $label)
                @php
                    $pref = $prefs[$key] ?? null;
                    $email = old("prefs.$key.email", $pref?->email_enabled ?? false);
                    $push  = old("prefs.$key.push",  $pref?->push_enabled  ?? false);
                @endphp
                <li class="grid grid-cols-12 gap-4 items-center px-5 py-4 border-b border-algeciras-black/5 last:border-b-0">
                    <div class="col-span-12 md:col-span-8">
                        <p class="font-display text-base">{{ $label }}</p>
                    </div>
                    <div class="col-span-6 md:col-span-2 flex items-center justify-center gap-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="prefs[{{ $key }}][email]" value="1" {{ $email ? 'checked' : '' }}
                                   class="w-5 h-5 accent-algeciras-red">
                            <span class="md:hidden text-xs font-display tracking-widest uppercase">Email</span>
                        </label>
                    </div>
                    <div class="col-span-6 md:col-span-2 flex items-center justify-center gap-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="prefs[{{ $key }}][push]" value="1" {{ $push ? 'checked' : '' }}
                                   class="w-5 h-5 accent-algeciras-red">
                            <span class="md:hidden text-xs font-display tracking-widest uppercase">Push</span>
                        </label>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="flex justify-end p-5 bg-algeciras-cream">
            <button type="submit"
                    class="px-6 py-3 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase text-xs shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                Guardar preferencias →
            </button>
        </div>
    </form>

    <p class="text-xs text-algeciras-gray">
        Tu privacidad es importante. Usaremos solo estos canales para enviarte el contenido marcado.
        Puedes cambiarlo en cualquier momento.
    </p>
</div>

@endsection

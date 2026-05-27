@extends('pages.area-personal._layout')

@section('panel')

<div class="space-y-6">
    <header>
        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">Mis Datos</p>
        <h2 class="font-display text-3xl md:text-4xl uppercase leading-tight">Información personal</h2>
    </header>

    {{-- =================== DATOS ==================== --}}
    <form action="{{ route('area-personal.datos.update') }}" method="POST"
          class="bg-white border-2 border-algeciras-black/10 p-6 lg:p-8 space-y-5">
        @csrf

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Nombre completo</label>
                <input type="text" name="name" required value="{{ old('name', $user->name) }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Email</label>
                <input type="email" value="{{ $user->email }}" readonly disabled
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 bg-algeciras-cream font-mono text-sm text-algeciras-gray cursor-not-allowed">
                <p class="text-[10px] text-algeciras-gray mt-1">Para cambiar tu email, contacta con el club.</p>
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Nombre</label>
                <input type="text" name="first_name" value="{{ old('first_name', $customer?->first_name) }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Apellidos</label>
                <input type="text" name="last_name" value="{{ old('last_name', $customer?->last_name) }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Teléfono</label>
                <input type="tel" name="phone" value="{{ old('phone', $customer?->phone) }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">DNI</label>
                <input type="text" name="dni" value="{{ old('dni', $customer?->dni) }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Fecha de nacimiento</label>
                <input type="date" name="birth_date" value="{{ old('birth_date', optional($customer?->birth_date)->format('Y-m-d')) }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Idioma</label>
                <select name="language"
                        class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
                    @foreach(['es' => 'Español', 'en' => 'English'] as $code => $label)
                        <option value="{{ $code }}" {{ ($customer?->language ?? 'es') === $code ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <hr class="border-algeciras-black/10">
        <h3 class="font-display text-lg uppercase">Dirección</h3>

        <div class="grid sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Dirección</label>
                <input type="text" name="address" value="{{ old('address', $customer?->address) }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Ciudad</label>
                <input type="text" name="city" value="{{ old('city', $customer?->city) }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Provincia</label>
                <input type="text" name="province" value="{{ old('province', $customer?->province) }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Código postal</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $customer?->postal_code) }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">País</label>
                <input type="text" name="country" value="{{ old('country', $customer?->country ?? 'España') }}"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit"
                    class="px-6 py-3 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase text-xs shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                Guardar cambios →
            </button>
        </div>
    </form>

    {{-- =================== PASSWORD ==================== --}}
    <form action="{{ route('area-personal.password.update') }}" method="POST"
          class="bg-white border-2 border-algeciras-black/10 p-6 lg:p-8 space-y-5">
        @csrf

        <header>
            <h3 class="font-display text-xl uppercase">Cambiar contraseña</h3>
            <p class="text-xs text-algeciras-gray mt-1">Usa al menos 6 caracteres.</p>
        </header>

        <div class="grid sm:grid-cols-3 gap-5">
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Contraseña actual</label>
                <input type="password" name="current_password" required
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Nueva contraseña</label>
                <input type="password" name="password" required minlength="6"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
            <div>
                <label class="block font-display tracking-widest uppercase text-xs mb-2">Repetir nueva</label>
                <input type="password" name="password_confirmation" required minlength="6"
                       class="w-full px-3 py-2 border-2 border-algeciras-black/10 focus:border-algeciras-red focus:outline-none transition font-mono text-sm">
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="px-6 py-3 border-2 border-algeciras-black hover:bg-algeciras-black hover:text-white font-display tracking-widest uppercase text-xs transition">
                Cambiar contraseña →
            </button>
        </div>
    </form>
</div>

@endsection

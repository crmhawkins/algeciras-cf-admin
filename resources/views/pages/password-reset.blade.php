@extends('layouts.app')

@section('title', 'Restablecer contraseña')

@section('content')
<section class="container mx-auto px-4 lg:px-8 py-24 max-w-md">
    <h1 class="font-display text-4xl mb-2">Restablecer contraseña</h1>
    <p class="text-algeciras-gray mb-8 text-sm">Introduce tu nueva contraseña.</p>

    @if ($errors->any())
        <div class="bg-algeciras-red text-white p-3 mb-4 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4 bg-white border-2 border-algeciras-black/10 p-6">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $email) }}" required
                   class="w-full px-4 py-3 bg-white border-2 border-algeciras-black/10 focus:border-algeciras-red outline-none transition">
        </div>
        <div>
            <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">Nueva contraseña</label>
            <input type="password" name="password" required minlength="6"
                   class="w-full px-4 py-3 bg-white border-2 border-algeciras-black/10 focus:border-algeciras-red outline-none transition">
        </div>
        <div>
            <label class="text-xs font-mono uppercase tracking-widest text-algeciras-gray block mb-1">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" required minlength="6"
                   class="w-full px-4 py-3 bg-white border-2 border-algeciras-black/10 focus:border-algeciras-red outline-none transition">
        </div>
        <button type="submit" class="w-full px-6 py-4 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase shadow-brutal">
            Restablecer contraseña
        </button>
    </form>
</section>
@endsection

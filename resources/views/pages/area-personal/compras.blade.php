@extends('pages.area-personal._layout')

@section('panel')

<div class="space-y-6">
    <header>
        <p class="font-mono text-algeciras-red text-xs tracking-[0.4em] uppercase mb-1">Mis Compras</p>
        <h2 class="font-display text-3xl md:text-4xl uppercase leading-tight">Historial de pedidos</h2>
    </header>

    @if($orders->isEmpty())
        <div class="bg-white border-2 border-algeciras-black/10 p-10 text-center">
            <p class="text-5xl mb-3">📦</p>
            <h3 class="font-display text-2xl uppercase mb-2">Aún no tienes compras</h3>
            <p class="text-algeciras-gray text-sm mb-6">Visita la tienda oficial del club.</p>
            <a href="{{ route('tienda') }}"
               class="inline-block px-6 py-3 bg-algeciras-red hover:bg-algeciras-red-dark text-white font-display tracking-widest uppercase text-sm shadow-brutal hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition">
                Ir a la tienda →
            </a>
        </div>
    @else
        <div class="bg-white border-2 border-algeciras-black/10 overflow-hidden">
            {{-- Tabla en desktop, cards en móvil --}}
            <div class="hidden md:block">
                <table class="w-full text-sm">
                    <thead class="bg-algeciras-black text-white">
                        <tr>
                            <th class="text-left px-4 py-3 font-display tracking-widest uppercase text-xs">Referencia</th>
                            <th class="text-left px-4 py-3 font-display tracking-widest uppercase text-xs">Fecha</th>
                            <th class="text-left px-4 py-3 font-display tracking-widest uppercase text-xs">Estado</th>
                            <th class="text-right px-4 py-3 font-display tracking-widest uppercase text-xs">Total</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-algeciras-black/5">
                        @foreach($orders as $o)
                            <tr class="hover:bg-algeciras-cream transition">
                                <td class="px-4 py-3 font-mono">{{ $o->reference }}</td>
                                <td class="px-4 py-3">{{ optional($o->created_at)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $estadoStyle = match($o->status) {
                                            'paid'      => 'background:#16A34A;color:#fff;',
                                            'pending'   => 'background:#F59E0B;color:#fff;',
                                            'cancelled' => 'background:#71717A;color:#fff;',
                                            default     => 'background:#0A0A0A;color:#fff;',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 text-[10px] font-display tracking-widest uppercase"
                                          style="{{ $estadoStyle }}">
                                        {{ ucfirst($o->status ?? '—') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-display">{{ number_format((float) $o->total, 2, ',', '.') }} €</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('area-personal.compras.detalle', ['reference' => $o->reference]) }}"
                                       class="font-display tracking-widest uppercase text-xs text-algeciras-red hover:underline">
                                        Detalle →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Cards en móvil --}}
            <div class="md:hidden divide-y divide-algeciras-black/5">
                @foreach($orders as $o)
                    <a href="{{ route('area-personal.compras.detalle', ['reference' => $o->reference]) }}"
                       class="block p-4 hover:bg-algeciras-cream transition">
                        <div class="flex items-center justify-between mb-1">
                            <p class="font-mono text-sm">{{ $o->reference }}</p>
                            <p class="font-display">{{ number_format((float) $o->total, 2, ',', '.') }} €</p>
                        </div>
                        <p class="text-xs text-algeciras-gray">{{ optional($o->created_at)->format('d/m/Y') }} · {{ ucfirst($o->status ?? '—') }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>

@endsection

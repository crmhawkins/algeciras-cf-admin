<div style="display: flex; flex-direction: column; gap: 20px; font-family: Inter, sans-serif;">

    {{-- KPIs estado de butacas --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
        <div style="background: #F9FAFB; border: 1px solid #E5E7EB; padding: 14px; border-radius: 8px; text-align: center;">
            <div style="font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: .5px;">Total</div>
            <div style="font-size: 24px; font-weight: 800;">{{ number_format($resumen['total']) }}</div>
        </div>
        <div style="background: #ECFDF5; border: 1px solid #A7F3D0; padding: 14px; border-radius: 8px; text-align: center;">
            <div style="font-size: 11px; color: #047857; text-transform: uppercase; letter-spacing: .5px;">Libres</div>
            <div style="font-size: 24px; font-weight: 800; color: #047857;">{{ number_format($resumen['libres']) }}</div>
        </div>
        <div style="background: #EFF6FF; border: 1px solid #BFDBFE; padding: 14px; border-radius: 8px; text-align: center;">
            <div style="font-size: 11px; color: #1D4ED8; text-transform: uppercase; letter-spacing: .5px;">Vendidas</div>
            <div style="font-size: 24px; font-weight: 800; color: #1D4ED8;">{{ number_format($resumen['vendidas']) }}</div>
        </div>
        <div style="background: #FEF2F2; border: 1px solid #FECACA; padding: 14px; border-radius: 8px; text-align: center;">
            <div style="font-size: 11px; color: #B91C1C; text-transform: uppercase; letter-spacing: .5px;">Bloqueadas</div>
            <div style="font-size: 24px; font-weight: 800; color: #B91C1C;">{{ number_format($resumen['bloqueadas']) }}</div>
        </div>
    </div>

    {{-- Tabla por sector --}}
    <div>
        <h3 style="font-weight: 700; font-size: 16px; margin-bottom: 12px;">Estado por sector</h3>
        <div style="max-height: 460px; overflow-y: auto; border: 1px solid #E5E7EB; border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; background: #fff;">
                <thead style="position: sticky; top: 0; z-index: 1;">
                    <tr style="background: #F9FAFB;">
                        <th style="text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Sector</th>
                        <th style="text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Zona</th>
                        <th style="text-align: right; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Libres</th>
                        <th style="text-align: right; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Vendidas</th>
                        <th style="text-align: right; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Bloqueadas</th>
                        <th style="text-align: center; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sectores as $s)
                        @php
                            $stats = $resumen['por_sector'][$s->id] ?? collect();
                            $libres   = $stats->where('status', 'free')->first()?->c ?? 0;
                            $vendidas = $stats->where('status', 'sold')->first()?->c ?? 0;
                            $bloq     = $stats->where('status', 'blocked')->first()?->c ?? 0;
                        @endphp
                        <tr style="border-top: 1px solid #E5E7EB;">
                            <td style="padding: 10px 12px; font-weight: 600;">{{ $s->name }}</td>
                            <td style="padding: 10px 12px; color: #6B7280; font-size: 13px;">{{ $s->zone }}</td>
                            <td style="padding: 10px 12px; text-align: right; color: #047857; font-weight: 600; font-variant-numeric: tabular-nums;">{{ $libres }}</td>
                            <td style="padding: 10px 12px; text-align: right; color: #1D4ED8; font-weight: 600; font-variant-numeric: tabular-nums;">{{ $vendidas }}</td>
                            <td style="padding: 10px 12px; text-align: right; color: #B91C1C; font-weight: 600; font-variant-numeric: tabular-nums;">{{ $bloq }}</td>
                            <td style="padding: 10px 12px; text-align: center; display: flex; gap: 6px; justify-content: center;">
                                <button type="button"
                                        wire:click="cambiarEstadoSeats({{ $s->id }}, 'free')"
                                        wire:confirm="¿Marcar TODAS las butacas del sector {{ $s->name }} como LIBRES? Esta acción afecta {{ $stats->sum('c') }} butacas."
                                        style="background: #10B981; color: #fff; border: 0; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer;">
                                    Liberar
                                </button>
                                <button type="button"
                                        wire:click="cambiarEstadoSeats({{ $s->id }}, 'blocked')"
                                        wire:confirm="¿Bloquear TODAS las butacas del sector {{ $s->name }}?"
                                        style="background: #EF4444; color: #fff; border: 0; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer;">
                                    Bloquear
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="display: flex; flex-direction: column; gap: 24px; font-family: Inter, sans-serif;">

    {{-- KPIs --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
        <div style="background: linear-gradient(135deg, #2196F3, #1976D2); color: #fff; padding: 20px; border-radius: 8px;">
            <div style="font-size: 12px; opacity: .85; text-transform: uppercase; letter-spacing: 1px;">Total pedidos</div>
            <div style="font-size: 30px; font-weight: 800; margin-top: 4px;">{{ number_format($stats['total_pedidos']) }}</div>
        </div>
        <div style="background: linear-gradient(135deg, #10B981, #059669); color: #fff; padding: 20px; border-radius: 8px;">
            <div style="font-size: 12px; opacity: .85; text-transform: uppercase; letter-spacing: 1px;">Ingresos totales</div>
            <div style="font-size: 30px; font-weight: 800; margin-top: 4px;">{{ number_format($stats['total_ingresos'], 2, ',', '.') }} €</div>
        </div>
        <div style="background: linear-gradient(135deg, #F59E0B, #D97706); color: #fff; padding: 20px; border-radius: 8px;">
            <div style="font-size: 12px; opacity: .85; text-transform: uppercase; letter-spacing: 1px;">Ticket medio</div>
            <div style="font-size: 30px; font-weight: 800; margin-top: 4px;">
                {{ $stats['total_pedidos'] > 0
                    ? number_format($stats['total_ingresos'] / $stats['total_pedidos'], 2, ',', '.')
                    : '0,00' }} €
            </div>
        </div>
        <div style="background: linear-gradient(135deg, #6B7280, #4B5563); color: #fff; padding: 20px; border-radius: 8px;">
            <div style="font-size: 12px; opacity: .85; text-transform: uppercase; letter-spacing: 1px;">Últimos 30 días</div>
            <div style="font-size: 30px; font-weight: 800; margin-top: 4px;">
                {{ $stats['por_dia_ultimos_30']->sum('c') }}
            </div>
        </div>
    </div>

    {{-- Distribución por canal --}}
    <div>
        <h3 style="font-weight: 700; font-size: 16px; margin-bottom: 12px;">Distribución por canal de venta</h3>
        <table style="width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #E5E7EB; border-radius: 8px; overflow: hidden;">
            <thead>
                <tr style="background: #F9FAFB;">
                    <th style="text-align: left; padding: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Canal</th>
                    <th style="text-align: right; padding: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Pedidos</th>
                    <th style="text-align: right; padding: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Ingresos</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stats['por_canal'] as $c)
                    <tr style="border-top: 1px solid #E5E7EB;">
                        <td style="padding: 12px; font-weight: 600;">{{ strtoupper($c->channel) }}</td>
                        <td style="padding: 12px; text-align: right; font-variant-numeric: tabular-nums;">{{ $c->c }}</td>
                        <td style="padding: 12px; text-align: right; font-variant-numeric: tabular-nums; font-weight: 600;">{{ number_format((float) $c->t, 2, ',', '.') }} €</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="padding: 24px; text-align: center; color: #6B7280;">Sin ventas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Últimas ventas --}}
    <div>
        <h3 style="font-weight: 700; font-size: 16px; margin-bottom: 12px;">Últimas {{ count($ultimas) }} ventas</h3>
        <div style="max-height: 380px; overflow-y: auto; border: 1px solid #E5E7EB; border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; background: #fff;">
                <thead style="position: sticky; top: 0;">
                    <tr style="background: #F9FAFB;">
                        <th style="text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Referencia</th>
                        <th style="text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Fecha</th>
                        <th style="text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Cliente</th>
                        <th style="text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Producto</th>
                        <th style="text-align: right; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6B7280;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ultimas as $o)
                        <tr style="border-top: 1px solid #E5E7EB;">
                            <td style="padding: 10px 12px; font-family: monospace; font-size: 12px;">{{ $o->reference }}</td>
                            <td style="padding: 10px 12px; font-size: 13px; color: #6B7280;">{{ optional($o->paid_at)->format('d/m/Y H:i') }}</td>
                            <td style="padding: 10px 12px;">
                                {{ trim(optional($o->customer)->first_name . ' ' . optional($o->customer)->last_name) ?: '(invitado)' }}
                            </td>
                            <td style="padding: 10px 12px; font-size: 13px;">{{ optional($o->items->first()?->product)->name ?? '—' }}</td>
                            <td style="padding: 10px 12px; text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;">{{ number_format((float) $o->total, 2, ',', '.') }} €</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding: 24px; text-align: center; color: #6B7280;">Sin ventas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

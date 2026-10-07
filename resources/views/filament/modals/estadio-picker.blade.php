{{-- Modal del plano del estadio. Se abre desde el botón "Elegir butaca" del
     form de Renovación / Abono nuevo. El iframe carga /estadio?embed=admin
     (filtrado por zona del producto si se conoce) y al click en una butaca
     envía postMessage al window.parent — este script de aquí escucha,
     actualiza los Hidden inputs sector_id / seat_id del form Filament y
     cierra el modal. --}}
@php
    $iframeSrc = '/estadio?embed=admin';
    if (!empty($zoneSlug)) {
        $iframeSrc .= '&zone=' . urlencode($zoneSlug);
    }
@endphp

<div class="space-y-3">
    @if (empty($productId))
        <div class="rounded-lg bg-amber-50 border border-amber-300 p-3 text-sm text-amber-900">
            ⚠️ Selecciona primero un <strong>producto abono</strong> en el formulario antes de elegir butaca, para que el plano se filtre a la zona correcta.
        </div>
    @elseif (!empty($zoneSlug))
        <div class="rounded-lg bg-emerald-50 border border-emerald-300 p-3 text-sm text-emerald-900">
            ✅ Plano filtrado por zona <strong>{{ $zoneSlug }}</strong>. Los sectores que no son válidos para este abono aparecerán atenuados.
        </div>
    @endif

    <iframe
        src="{{ url($iframeSrc) }}"
        id="cm-modal-estadio-iframe"
        class="w-full rounded-lg border-2 border-gray-200"
        style="height: 75vh; min-height: 600px;"
        title="Plano del estadio"
    ></iframe>
</div>

<script>
    (function () {
        function onSeatPick(e) {
            if (!e.data || e.data.type !== 'algeciras-admin-seat-pick') return;

            // 1) Actualizar Hidden inputs en el form (vía Livewire wire api)
            try {
                // Buscar componente Livewire que contiene los inputs sector/seat
                const wireRoots = [...document.querySelectorAll('[wire\\:id]')];
                // Usar el más exterior (la página)
                const cmp = wireRoots.length ? window.Livewire?.find?.(wireRoots[0].getAttribute('wire:id')) : null;
                if (cmp) {
                    cmp.set('data.sector_id', e.data.sector_id);
                    cmp.set('data.seat_id',   e.data.seat_id);
                }
            } catch (err) { console.warn('No pude setear vía Livewire', err); }

            // 2) Mostrar notificación visual breve
            const msg = '✅ Butaca elegida: ' + e.data.sector_name + ' · Fila ' + e.data.row + ' · Butaca ' + e.data.number;
            try {
                if (window.$wireui?.notify) {
                    window.$wireui.notify({ title: 'Asiento guardado', description: msg, icon: 'success' });
                }
            } catch {}

            // 3) Cerrar el modal de Filament (event mountedAction)
            try {
                const cmp = window.Livewire?.first?.();
                cmp?.call('mountedFormComponentActions', []) // intenta vaciar mounted actions
                  ?.then?.(() => {});
                // Alternativa universal: disparar Escape
                document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', keyCode: 27 }));
            } catch {}
        }
        window.addEventListener('message', onSeatPick);
        // Limpieza por si el modal se reabre varias veces
        const iframe = document.getElementById('cm-modal-estadio-iframe');
        if (iframe) {
            iframe.addEventListener('load', function () {
                // El iframe se cargó; nada extra que hacer porque el script
                // del plano ya conoce ?embed=admin
            });
        }
    })();
</script>

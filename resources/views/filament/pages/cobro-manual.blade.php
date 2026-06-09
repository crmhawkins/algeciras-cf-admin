<x-filament-panels::page>
    <div class="space-y-6"
         x-data="{
            estadioModalOpen: false,
            iframeSrc: '',
            zoneSlug: null,
            productZones: @js(\App\Models\Product::where('type','abono')->where('active',1)->with('zone:id,slug,name')->get()->mapWithKeys(fn($p)=>[$p->id=>$p->zone?->slug])),
            openEstadioModal() {
                // Detectar product_id del FORM Filament (iterar wire:ids y filtrar
                // por data.first_name como huella, igual que el listener postMessage).
                let pid = null;
                try {
                    const roots = [...document.querySelectorAll('[wire\\:id]')];
                    for (const r of roots) {
                        const c = Livewire.find(r.getAttribute('wire:id'));
                        const d = c?.get?.('data');
                        if (d && 'first_name' in d && 'product_id' in d) {
                            pid = d.product_id || null;
                            break;
                        }
                    }
                } catch (e) {}
                this.zoneSlug = pid ? this.productZones[pid] : null;
                this.iframeSrc = '{{ url('/estadio?embed=admin') }}' + (this.zoneSlug ? '&zone=' + encodeURIComponent(this.zoneSlug) : '');
                this.estadioModalOpen = true;
            },
            closeEstadioModal() { this.estadioModalOpen = false; this.iframeSrc = ''; }
         }"
         @seat-picked.window="closeEstadioModal()">

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200">
            <strong>Cobro manual.</strong> Esta pantalla se usa en taquilla / oficina del club
            para registrar ventas en efectivo, bizum, transferencia o TPV físico.
            Genera el mismo Order + Ticket QR que una compra online.
        </div>

        {{ $this->form }}

        @if (in_array(get_class($this), [App\Filament\Pages\AbonoNuevo::class, App\Filament\Pages\RenovacionAbono::class]))
            {{-- Botón Alpine que dispara el modal con el plano --}}
            <div class="flex">
                <button type="button"
                        @click="openEstadioModal()"
                        class="inline-flex items-center gap-3 px-6 py-4 rounded-xl
                               bg-red-600 hover:bg-red-700 text-white font-bold text-lg
                               shadow-lg hover:shadow-xl transition-all">
                    🏟️ Elegir butaca en el plano del estadio
                </button>
            </div>

            {{-- Modal Alpine teleportado al body para que Livewire/Filament no
                 reseteen el style en cada morphdom. Sin teleport se renderiza
                 inline dentro del form en vez de como overlay flotante. --}}
            <template x-teleport="body">
            <div x-show="estadioModalOpen"
                 x-cloak
                 style="position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,.85); display: flex; align-items: center; justify-content: center; padding: 16px;"
                 @keydown.escape.window="closeEstadioModal()">
                <div style="background: #fff; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,.6); width: 96vw; max-width: 1600px; height: 92vh; display: flex; flex-direction: column; overflow: hidden;">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: linear-gradient(to right, #dc2626, #b91c1c); color: white; flex-shrink: 0;">
                        <div>
                            <div style="font-weight: 700; font-size: 18px;">🏟️ Plano del Estadio Nuevo Mirador</div>
                            <div style="font-size: 12px; opacity: .9;">
                                Sectores válidos para este abono resaltados. Click en sector → click en butaca → se guarda y cierra automáticamente.
                            </div>
                        </div>
                        <button type="button"
                                @click="closeEstadioModal()"
                                style="background: rgba(255,255,255,.15); color: white; border: 0; border-radius: 50%; width: 36px; height: 36px; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center;">×</button>
                    </div>
                    <iframe x-bind:src="iframeSrc"
                            style="flex: 1 1 auto; width: 100%; border: 0; min-height: 0; display: block;"
                            title="Plano del estadio"></iframe>
                </div>
            </div>
            </template>

            <script>
                // Listener postMessage del iframe: actualiza inputs del form + cierra modal
                (function () {
                    if (window.__cmListenerInstalled) return;
                    window.__cmListenerInstalled = true;
                    window.addEventListener('message', function (e) {
                        if (!e.data || e.data.type !== 'algeciras-admin-seat-pick') return;
                        try {
                            // Buscar EL componente del form Filament (tiene data.first_name).
                            // querySelector('[wire:id]') no sirve: pilla notificaciones u otros.
                            const roots = [...document.querySelectorAll('[wire\\:id]')];
                            let formCmp = null;
                            for (const r of roots) {
                                const c = Livewire.find(r.getAttribute('wire:id'));
                                const d = c?.get?.('data');
                                if (d && 'first_name' in d && 'sector_id' in d) { formCmp = c; break; }
                            }
                            if (formCmp) {
                                formCmp.set('data.sector_id', e.data.sector_id);
                                formCmp.set('data.seat_id',   e.data.seat_id);
                            } else {
                                console.warn('cobro-manual: form Livewire no encontrado');
                            }
                        } catch (err) { console.warn('No pude setear', err); }
                        // Disparar evento Alpine para que cierre el modal
                        window.dispatchEvent(new CustomEvent('seat-picked', { detail: e.data }));
                    });
                })();
            </script>
        @endif
    </div>
</x-filament-panels::page>

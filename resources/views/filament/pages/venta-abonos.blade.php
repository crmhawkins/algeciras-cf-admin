<x-filament-panels::page>
    {{-- =================================================================
         VENTA DE ABONOS — Pagina principal (replica UI del cliente)
         ================================================================= --}}
<div x-data="{
        confirmOpen: false,
        pendingId: null,
        pendingNombre: '',
        pendingAsiento: '',
        metodo: 'efectivo',
        abrirConfirm(id, nombre, asiento) {
            this.pendingId = id;
            this.pendingNombre = nombre;
            this.pendingAsiento = asiento;
            this.metodo = 'efectivo';
            this.confirmOpen = true;
        },
        confirmar() {
            this.confirmOpen = false;
            $wire.renovacionRapida(this.pendingId, this.metodo);
        }
     }">

    {{-- 1. RENOVACION DE ABONOS --}}
    <div class="va-renovacion-card">
        <div class="va-section-title">1. Renovación de abonos</div>
        <div class="va-section-desc">Renovación de abonos para abonados existentes.</div>

        <div class="va-filtros-row">
            <span class="va-filtros-label">FILTROS:</span>

            <label class="va-radio-pill">
                <input type="radio" wire:model.live="filtroRenovacion" value="numero_abono">
                <span class="va-radio-label">Nº Abonado</span>
            </label>
            <label class="va-radio-pill">
                <input type="radio" wire:model.live="filtroRenovacion" value="dni">
                <span class="va-radio-label">DNI</span>
            </label>
            <label class="va-radio-pill">
                <input type="radio" wire:model.live="filtroRenovacion" value="apellidos">
                <span class="va-radio-label">Apellidos</span>
            </label>
        </div>

        <input
            type="text"
            class="va-search-input"
            wire:model="busquedaRenovacion"
            placeholder="Buscar abonado"
            wire:keydown.enter="buscarAbonadoRenovacion">

        <button type="button" wire:click="buscarAbonadoRenovacion" class="va-submit-btn">
            ENVIAR
        </button>

        @if (count($renovacionResultados))
            <div style="margin-top: 28px;">
                <div style="font-size: 22px; font-weight: 800; color: var(--acf-text); margin-bottom: 16px; letter-spacing: .5px;">
                    RESULTADOS ENCONTRADOS
                </div>
                <div style="display: flex; flex-direction: column;">
                    @foreach ($renovacionResultados as $r)
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 0; border-bottom: 1px solid var(--acf-border); flex-wrap: wrap;">
                            {{-- Datos del abonado --}}
                            <div style="flex: 1 1 320px; min-width: 280px;">
                                <div style="font-weight: 800; color: var(--acf-text); font-size: 17px; text-transform: uppercase; letter-spacing: .3px;">
                                    {{ $r['nombre'] }}
                                    @if ($r['renovado'])
                                        <span style="display: inline-block; margin-left: 8px; background: #111827; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 3px; letter-spacing: .5px; vertical-align: middle;">RENOVADO</span>
                                    @endif
                                </div>
                                <div style="margin-top: 6px; color: var(--acf-text); font-size: 14px;">
                                    Nº ABONADO: <span style="color: var(--acf-blue); font-weight: 700;">{{ $r['socio_number'] ?? '—' }}</span>
                                    &nbsp;/&nbsp; DNI: <span style="color: var(--acf-blue); font-weight: 700;">{{ $r['dni'] ?? '—' }}</span>
                                </div>
                                @if (!empty($r['asiento']))
                                    <div style="margin-top: 4px; color: var(--acf-blue); font-size: 14px; font-weight: 700; text-transform: uppercase;">
                                        {{ $r['asiento'] }}
                                    </div>
                                @else
                                    <div style="margin-top: 4px; color: #B45309; font-size: 13px;">
                                        ⚠️ Sin asiento de temporada anterior
                                    </div>
                                @endif
                            </div>

                            {{-- Botones --}}
                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <button type="button"
                                        @if (empty($r['asiento']) || $r['renovado']) disabled @endif
                                        x-on:click="abrirConfirm({{ $r['id'] }}, @js($r['nombre']), @js($r['asiento'] ?? ''))"
                                        style="background: {{ (empty($r['asiento']) || $r['renovado']) ? '#9CA3AF' : '#111827' }}; color: #fff; border: 0; padding: 12px 22px; font-weight: 700; font-size: 12px; letter-spacing: .5px; text-transform: uppercase; border-radius: 4px; cursor: {{ (empty($r['asiento']) || $r['renovado']) ? 'not-allowed' : 'pointer' }};">
                                    Renovación rápida
                                </button>
                                <button type="button" wire:click="irACobroRenovacion({{ $r['id'] }})"
                                        style="background: #111827; color: #fff; border: 0; padding: 12px 22px; font-weight: 700; font-size: 12px; letter-spacing: .5px; text-transform: uppercase; border-radius: 4px; cursor: pointer;">
                                    Cambiar asiento / Incluir más abonos
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Modal de confirmacion de renovacion rapida (Alpine, no nativo) --}}
    <template x-teleport="body">
        <div x-show="confirmOpen" x-cloak
             style="position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,.6); display: flex; align-items: center; justify-content: center; padding: 16px;"
             x-on:keydown.escape.window="confirmOpen = false">
            <div style="background: #fff; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,.5); width: 100%; max-width: 460px; overflow: hidden;"
                 x-on:click.outside="confirmOpen = false">
                <div style="background: var(--acf-blue); color: #fff; padding: 16px 20px; font-weight: 700; font-size: 16px;">
                    Renovación rápida
                </div>
                <div style="padding: 20px;">
                    <div style="font-size: 15px; color: #111827; margin-bottom: 4px;">
                        Renovar el abono de <strong x-text="pendingNombre"></strong>
                    </div>
                    <div style="font-size: 13px; color: #6B7280; margin-bottom: 18px;">
                        Mismo asiento: <strong x-text="pendingAsiento"></strong>
                    </div>
                    <div style="font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">Método de pago:</div>
                    <div style="display: flex; gap: 16px; margin-bottom: 22px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" value="efectivo" x-model="metodo" style="accent-color: var(--acf-blue);"> 💵 Efectivo
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" value="tpv_fisico" x-model="metodo" style="accent-color: var(--acf-blue);"> 💳 TPV físico
                        </label>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" x-on:click="confirmOpen = false"
                                style="background: #E5E7EB; color: #374151; border: 0; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">
                            Cancelar
                        </button>
                        <button type="button" x-on:click="confirmar()"
                                style="background: var(--acf-blue); color: #fff; border: 0; padding: 10px 24px; border-radius: 6px; font-weight: 700; cursor: pointer;">
                            Confirmar renovación
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- 2. NUEVAS ALTAS --}}
    <div class="va-altas-card"
         x-data="{
            seatSrc: null,
            sectorName: '',
            elegirZona(region, name) {
                this.sectorName = name;
                this.seatSrc = '{{ url('/estadio/sector') }}/' + region + '?embed=admin&alta=1';
            },
            volver() { this.seatSrc = null; this.sectorName = ''; }
         }">
        <div class="va-section-title">2. Nuevas altas</div>
        <div class="va-section-desc">Selecciona el sector y elige la butaca directamente.</div>

        {{-- VISTA A: tabla de zonas + plano (cuando no hay zona elegida) --}}
        <div x-show="!seatSrc" class="va-altas-grid">

            {{-- Columna izquierda: buscador + tabla scrolleable de sectores. --}}
            <div>
                <input
                    type="text"
                    class="va-sectors-search"
                    wire:model.live.debounce.300ms="busquedaSector"
                    placeholder="Buscar...">

                <div class="va-sectors-table">
                    <table>
                        <thead>
                            <tr>
                                <th>SECTOR</th>
                                <th style="text-align: right;">LIBRES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->sectores as $s)
                                <tr
                                    x-on:click="elegirZona({{ $s->svg_region }}, @js(strtoupper($s->name)))"
                                    style="cursor: pointer;">
                                    <td>{{ strtoupper($s->name) }}</td>
                                    <td style="text-align: right; font-variant-numeric: tabular-nums;">
                                        {{ $s->seats_free }} / {{ $s->seats_total }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" style="text-align: center; padding: 24px; color: var(--acf-text-muted);">Sin sectores que coincidan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 14px; font-size: 13px; color: var(--acf-text-muted);">
                    👆 Pulsa una zona para elegir la butaca aquí mismo.
                </div>
            </div>

            {{-- Columna derecha: plano de zonas (referencia visual) --}}
            <div class="va-stadium-plan">
                <iframe src="{{ url('/estadio?embed=admin') }}" title="Plano del estadio"></iframe>
            </div>
        </div>

        {{-- VISTA B: grilla de butacas EN IFRAME (dentro del panel, menú visible) --}}
        <div x-show="seatSrc" x-cloak>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; flex-wrap: wrap;">
                <div style="font-weight: 700; font-size: 16px; color: var(--acf-text);">
                    Zona seleccionada: <span style="color: var(--acf-blue);" x-text="sectorName"></span>
                </div>
                <button type="button" x-on:click="volver()"
                        style="background: #111827; color: #fff; border: 0; padding: 10px 20px; border-radius: 4px; font-weight: 700; font-size: 12px; letter-spacing: .5px; text-transform: uppercase; cursor: pointer;">
                    ← Cambiar zona
                </button>
            </div>
            <iframe x-bind:src="seatSrc"
                    style="width: 100%; height: 680px; border: 1px solid var(--acf-border); border-radius: 8px; background: #fff;"
                    title="Selector de butacas"></iframe>
        </div>
    </div>

    {{-- Selector de idioma flotante esquina inferior izquierda (como en la imagen) --}}
    <div class="va-lang-fab" title="Idioma">文A</div>
</div>
</x-filament-panels::page>

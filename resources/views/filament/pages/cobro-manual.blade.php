<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200">
            <strong>Cobro manual.</strong> Esta pantalla se usa en taquilla / oficina del club
            para registrar ventas en efectivo, bizum, transferencia o TPV físico.
            Genera el mismo Order + Ticket QR que una compra online.
        </div>

        {{ $this->form }}
    </div>
</x-filament-panels::page>

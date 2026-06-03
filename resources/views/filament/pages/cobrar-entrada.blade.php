<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-900/40 dark:bg-sky-900/20 dark:text-sky-200">
            <strong>Cobrar entrada — flujo rápido.</strong><br>
            Selecciona partido + tipo de entrada + cliente (existente o nuevo) +
            método de pago. El cliente recibirá email automático con su QR.
        </div>

        {{ $this->form }}
    </div>
</x-filament-panels::page>

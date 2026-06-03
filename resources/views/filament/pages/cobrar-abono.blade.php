<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200">
            <strong>Cobrar abono — flujo rápido.</strong><br>
            • <strong>Renovación</strong>: escribe nº de socio o DNI → se cargan los datos solos.<br>
            • <strong>Nuevo</strong>: rellena los datos del cliente; se le creará cuenta y recibirá email con credenciales.<br>
            En ambos casos el cliente recibe email automático con su QR del abono.
        </div>

        {{ $this->form }}
    </div>
</x-filament-panels::page>

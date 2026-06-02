<script setup lang="ts">
import { computed } from 'vue';
import type { ValidationResult } from '../services/api';

const props = defineProps<{ result: ValidationResult | null; offline?: boolean }>();

const isOk = computed(() => props.result?.valid === true);
const isKo = computed(() => props.result?.valid === false);

const bg = computed(() => {
  if (props.offline) return 'bg-yellow-500';
  if (isOk.value) return 'bg-green-600';
  if (isKo.value) return 'bg-red-600';
  return 'bg-neutral-800';
});

const title = computed(() => {
  if (props.offline) return 'SIN CONEXION';
  if (isOk.value) return 'ACCESO AUTORIZADO';
  if (isKo.value) return 'ACCESO DENEGADO';
  return '';
});

const okData = computed(() => {
  if (!props.result?.valid) return null;
  return props.result;
});
</script>

<template>
  <div
    v-if="result || offline"
    :class="bg"
    class="fixed inset-0 z-50 flex flex-col items-center justify-center text-white px-6 text-center"
  >
    <div class="text-7xl mb-4">
      <template v-if="isOk">OK</template>
      <template v-else-if="offline">!</template>
      <template v-else>X</template>
    </div>
    <div class="text-3xl font-black uppercase tracking-wider mb-6">{{ title }}</div>

    <template v-if="isOk && okData">
      <div class="text-2xl font-semibold mb-2">{{ okData.ticket.customer_name }}</div>
      <div class="text-lg opacity-90 mb-1">
        {{ okData.type === 'abono' ? 'ABONO' : 'ENTRADA' }}
        <span v-if="okData.ticket.type_label">- {{ okData.ticket.type_label }}</span>
      </div>
      <div v-if="okData.ticket.zone?.name" class="text-lg opacity-90">
        {{ okData.ticket.zone.name }}
      </div>
      <div v-if="okData.ticket.fila || okData.ticket.asiento" class="text-lg opacity-90 mt-1">
        <span v-if="okData.ticket.fila">Fila {{ okData.ticket.fila }}</span>
        <span v-if="okData.ticket.fila && okData.ticket.asiento"> - </span>
        <span v-if="okData.ticket.asiento">Asiento {{ okData.ticket.asiento }}</span>
      </div>
    </template>

    <template v-else-if="isKo">
      <div class="text-xl font-medium max-w-md">{{ (result as any).message }}</div>
      <div class="text-sm opacity-70 mt-2">code: {{ (result as any).reason }}</div>
    </template>

    <template v-else-if="offline">
      <div class="text-xl font-medium max-w-md">
        El escaneo necesita internet para validar. Reintenta cuando recuperes señal.
      </div>
    </template>
  </div>
</template>

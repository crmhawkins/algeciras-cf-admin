<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const operatorName = ref('');
const pin = ref('');
const error = ref('');

function submit() {
  error.value = '';
  if (!operatorName.value.trim()) {
    error.value = 'Introduce tu nombre';
    return;
  }
  if (!/^\d{4,8}$/.test(pin.value)) {
    error.value = 'PIN invalido';
    return;
  }
  if (!auth.login(operatorName.value, pin.value)) {
    error.value = 'Credenciales incorrectas';
    return;
  }
  router.replace({ name: 'matches' });
}
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-6 bg-neutral-950">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <div class="inline-block bg-cf-red px-4 py-2 rounded-md text-white text-xs font-bold uppercase tracking-wider mb-3">
          Algeciras CF
        </div>
        <h1 class="text-2xl font-bold text-white">Validador de Aforo</h1>
        <p class="text-neutral-400 text-sm mt-1">Acceso solo personal autorizado</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4 bg-neutral-900 p-6 rounded-lg border border-neutral-800">
        <div>
          <label class="block text-xs uppercase text-neutral-400 mb-1">Nombre del operador</label>
          <input
            v-model="operatorName"
            type="text"
            autocomplete="off"
            class="w-full bg-neutral-800 border border-neutral-700 rounded-md px-3 py-3 text-white text-lg focus:outline-none focus:border-cf-red"
            placeholder="Ej: Juan Garcia"
          />
        </div>
        <div>
          <label class="block text-xs uppercase text-neutral-400 mb-1">PIN</label>
          <input
            v-model="pin"
            type="password"
            inputmode="numeric"
            maxlength="8"
            autocomplete="off"
            class="w-full bg-neutral-800 border border-neutral-700 rounded-md px-3 py-3 text-white text-2xl tracking-widest text-center focus:outline-none focus:border-cf-red"
            placeholder="****"
          />
        </div>

        <div v-if="error" class="text-red-400 text-sm text-center">{{ error }}</div>

        <button
          type="submit"
          class="w-full bg-cf-red hover:bg-red-700 text-white font-bold py-3 rounded-md uppercase tracking-wider"
        >
          Entrar
        </button>
      </form>

      <p class="text-center text-xs text-neutral-600 mt-6">
        Auth provisional - migrar a Sanctum
      </p>
    </div>
  </div>
</template>

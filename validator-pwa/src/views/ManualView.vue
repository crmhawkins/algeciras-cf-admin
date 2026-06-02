<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useMatchStore } from '../stores/match';
import { validarQR, type ValidationResult } from '../services/api';
import { extractToken } from '../services/scanner';
import HeaderBar from '../components/HeaderBar.vue';
import ResultBanner from '../components/ResultBanner.vue';

const router = useRouter();
const matchStore = useMatchStore();

const input = ref('');
const validating = ref(false);
const result = ref<ValidationResult | null>(null);
const offlineBanner = ref(false);

let timer: number | null = null;

async function submit() {
  if (validating.value) return;
  const token = extractToken(input.value);
  if (!token) return;

  validating.value = true;
  if (!navigator.onLine) {
    offlineBanner.value = true;
    schedule();
    validating.value = false;
    return;
  }

  try {
    result.value = await validarQR({
      token,
      match_id: matchStore.matchId!,
      gate_id: matchStore.gateId || undefined
    });
  } catch {
    result.value = {
      valid: false,
      reason: 'client_error',
      message: 'Error inesperado'
    };
  } finally {
    validating.value = false;
    schedule();
  }
}

function schedule() {
  if (timer) window.clearTimeout(timer);
  timer = window.setTimeout(() => {
    result.value = null;
    offlineBanner.value = false;
    input.value = '';
  }, 3500);
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-neutral-950">
    <HeaderBar title="Validacion manual" back />

    <main class="flex-1 px-4 py-6 max-w-md mx-auto w-full">
      <form @submit.prevent="submit" class="space-y-4 bg-neutral-900 p-6 rounded-lg border border-neutral-800">
        <p class="text-neutral-300 text-sm">
          Pega la URL del QR o teclea el token. Util si la pistola no lee bien.
        </p>
        <textarea
          v-model="input"
          rows="3"
          autofocus
          autocomplete="off"
          spellcheck="false"
          class="w-full bg-neutral-800 border border-neutral-700 rounded-md px-3 py-3 text-white text-lg font-mono focus:outline-none focus:border-cf-red"
          placeholder="https://algecirascf.hawkins.es/v/..."
        />
        <button
          type="submit"
          :disabled="validating || !input.trim()"
          class="w-full bg-cf-red hover:bg-red-700 disabled:bg-neutral-700 text-white font-bold py-3 rounded-md uppercase tracking-wider"
        >
          {{ validating ? 'Validando...' : 'Validar' }}
        </button>
        <button
          type="button"
          @click="router.replace({ name: 'scan' })"
          class="w-full text-neutral-400 hover:text-white text-sm"
        >
          Volver al escaner
        </button>
      </form>
    </main>

    <ResultBanner :result="result" :offline="offlineBanner" />
  </div>
</template>

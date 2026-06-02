<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const email = ref('');
const password = ref('');
const localError = ref('');

const error = computed(() => localError.value || auth.error);
const loading = computed(() => auth.loading);

async function submit() {
  localError.value = '';
  if (!email.value.trim()) {
    localError.value = 'Introduce tu email';
    return;
  }
  if (!password.value) {
    localError.value = 'Introduce tu contraseña';
    return;
  }
  const ok = await auth.login(email.value, password.value);
  if (ok) {
    router.replace({ name: 'matches' });
  }
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
          <label class="block text-xs uppercase text-neutral-400 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            autocomplete="username"
            inputmode="email"
            class="w-full bg-neutral-800 border border-neutral-700 rounded-md px-3 py-3 text-white text-lg focus:outline-none focus:border-cf-red"
            placeholder="operador@algecirascf.es"
          />
        </div>
        <div>
          <label class="block text-xs uppercase text-neutral-400 mb-1">Contraseña</label>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            class="w-full bg-neutral-800 border border-neutral-700 rounded-md px-3 py-3 text-white text-lg focus:outline-none focus:border-cf-red"
            placeholder="********"
          />
        </div>

        <div v-if="error" class="text-red-400 text-sm text-center">{{ error }}</div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-cf-red hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 rounded-md uppercase tracking-wider"
        >
          {{ loading ? 'Entrando…' : 'Entrar' }}
        </button>
      </form>

      <p class="text-center text-xs text-neutral-600 mt-6">
        Autenticación segura · Sanctum
      </p>
    </div>
  </div>
</template>

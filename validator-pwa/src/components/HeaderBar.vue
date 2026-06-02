<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useMatchStore } from '../stores/match';

defineProps<{ title?: string; back?: boolean }>();

const router = useRouter();
const auth = useAuthStore();
const match = useMatchStore();

function logout() {
  auth.logout();
  match.clear();
  router.replace({ name: 'login' });
}
</script>

<template>
  <header class="flex items-center justify-between px-4 py-3 bg-neutral-900 border-b border-neutral-800">
    <div class="flex items-center gap-2">
      <button v-if="back" @click="router.back()" class="text-neutral-400 hover:text-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </button>
      <div>
        <div class="text-xs text-cf-red font-bold tracking-wider uppercase">Algeciras CF</div>
        <div class="text-sm font-semibold">{{ title || 'Validador' }}</div>
      </div>
    </div>
    <div class="flex items-center gap-3 text-xs text-neutral-400">
      <span v-if="match.matchLabel" class="hidden sm:inline truncate max-w-[180px]">{{ match.matchLabel }}</span>
      <span v-if="auth.operatorName" class="hidden sm:inline">{{ auth.operatorName }}</span>
      <button @click="logout" class="text-neutral-500 hover:text-cf-red">Salir</button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useMatchStore } from '../stores/match';
import { fetchMatchStats, type MatchStats } from '../services/api';
import HeaderBar from '../components/HeaderBar.vue';

const router = useRouter();
const matchStore = useMatchStore();

const stats = ref<MatchStats | null>(null);
const error = ref('');
const loading = ref(true);

let timer: number | null = null;

async function load() {
  if (!matchStore.matchId) return;
  try {
    stats.value = await fetchMatchStats(matchStore.matchId);
    error.value = '';
  } catch (e) {
    error.value = 'No se pudieron cargar las estadisticas.';
  } finally {
    loading.value = false;
  }
}

const pct = computed(() => {
  if (!stats.value || !stats.value.capacity) return 0;
  return Math.min(100, Math.round((stats.value.entered / stats.value.capacity) * 100));
});

function sectorPct(s: { entered: number; capacity: number }) {
  if (!s.capacity) return 0;
  return Math.min(100, Math.round((s.entered / s.capacity) * 100));
}

function formatNum(n: number): string {
  return new Intl.NumberFormat('es-ES').format(n);
}

onMounted(() => {
  load();
  timer = window.setInterval(load, 10_000);
});

onBeforeUnmount(() => {
  if (timer) window.clearInterval(timer);
});
</script>

<template>
  <div class="min-h-screen flex flex-col bg-neutral-950">
    <HeaderBar title="Estadisticas en vivo" back />

    <main class="flex-1 px-4 py-6 max-w-2xl mx-auto w-full">
      <div v-if="loading && !stats" class="text-center text-neutral-400 py-12">Cargando...</div>

      <div v-else-if="error && !stats" class="text-center text-red-400 py-12">
        {{ error }}
        <button @click="load" class="block mx-auto mt-4 bg-cf-red px-4 py-2 rounded-md text-white">
          Reintentar
        </button>
      </div>

      <div v-else-if="stats" class="space-y-6">
        <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6 text-center">
          <div class="text-xs uppercase text-neutral-500">Aforo</div>
          <div class="text-5xl font-black text-white my-2">
            {{ formatNum(stats.entered) }}
            <span class="text-2xl text-neutral-500">/ {{ formatNum(stats.capacity) }}</span>
          </div>
          <div class="text-cf-red font-bold text-lg">{{ pct }}%</div>
          <div class="w-full bg-neutral-800 rounded-full h-3 mt-3 overflow-hidden">
            <div class="bg-cf-red h-full transition-all duration-500" :style="{ width: pct + '%' }" />
          </div>
        </div>

        <div v-if="stats.sectors && stats.sectors.length" class="space-y-3">
          <h2 class="text-sm uppercase text-neutral-400 font-bold">Por sector</h2>
          <div
            v-for="s in stats.sectors"
            :key="s.sector_id ?? s.sector_name"
            class="bg-neutral-900 border border-neutral-800 rounded-lg p-4"
          >
            <div class="flex justify-between items-baseline mb-2">
              <div class="text-white font-semibold">{{ s.sector_name }}</div>
              <div class="text-sm text-neutral-400">
                {{ formatNum(s.entered) }} / {{ formatNum(s.capacity) }}
                <span class="text-cf-red font-bold ml-1">{{ sectorPct(s) }}%</span>
              </div>
            </div>
            <div class="w-full bg-neutral-800 rounded-full h-2 overflow-hidden">
              <div class="bg-cf-red h-full transition-all duration-500" :style="{ width: sectorPct(s) + '%' }" />
            </div>
          </div>
        </div>

        <button
          @click="router.replace({ name: 'scan' })"
          class="w-full bg-cf-red hover:bg-red-700 text-white font-bold py-3 rounded-md uppercase tracking-wider"
        >
          Volver a escanear
        </button>
      </div>
    </main>
  </div>
</template>

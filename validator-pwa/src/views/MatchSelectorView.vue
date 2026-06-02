<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { fetchPartidos, type Match } from '../services/api';
import { useMatchStore } from '../stores/match';
import HeaderBar from '../components/HeaderBar.vue';

const router = useRouter();
const matchStore = useMatchStore();

const loading = ref(true);
const error = ref('');
const partidos = ref<Match[]>([]);

const homeScheduled = computed(() => {
  return [...partidos.value]
    .filter((m) => {
      const status = (m.status || '').toLowerCase();
      const venue = (m.venue || '').toLowerCase();
      const okStatus = status === 'scheduled' || status === 'programado' || status === '';
      const okVenue = venue === 'home' || venue === 'casa' || venue === '';
      return okStatus && okVenue;
    })
    .sort((a, b) => new Date(a.kickoff_at).getTime() - new Date(b.kickoff_at).getTime());
});

async function load() {
  loading.value = true;
  error.value = '';
  try {
    partidos.value = await fetchPartidos();
  } catch (e) {
    error.value = 'No se pudieron cargar los partidos. Revisa conexion.';
  } finally {
    loading.value = false;
  }
}

function formatDate(iso: string): string {
  if (!iso) return '';
  try {
    const d = new Date(iso);
    return d.toLocaleString('es-ES', {
      weekday: 'short',
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return iso;
  }
}

function pick(m: Match) {
  const label =
    m.label || `vs ${m.opponent_name || m.opponent || 'rival'} - ${formatDate(m.kickoff_at)}`;
  matchStore.setMatch(m.id, label);
  router.replace({ name: 'scan' });
}

onMounted(load);
</script>

<template>
  <div class="min-h-screen flex flex-col bg-neutral-950">
    <HeaderBar title="Selecciona partido" />

    <main class="flex-1 px-4 py-6 max-w-2xl mx-auto w-full">
      <div v-if="loading" class="text-center text-neutral-400 py-12">Cargando partidos...</div>

      <div v-else-if="error" class="text-center text-red-400 py-12">
        {{ error }}
        <button @click="load" class="block mx-auto mt-4 bg-cf-red px-4 py-2 rounded-md text-white">
          Reintentar
        </button>
      </div>

      <div v-else-if="homeScheduled.length === 0" class="text-center text-neutral-400 py-12">
        No hay partidos programados en casa.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="m in homeScheduled"
          :key="m.id"
          class="bg-neutral-900 border border-neutral-800 rounded-lg p-4 flex flex-col gap-2"
        >
          <div class="flex justify-between items-start">
            <div>
              <div class="text-xs text-neutral-500 uppercase">{{ m.competition || 'Liga' }}</div>
              <div class="text-lg font-semibold text-white">
                Algeciras CF vs {{ m.opponent_name || m.opponent || 'Rival' }}
              </div>
              <div class="text-sm text-neutral-400 mt-1">{{ formatDate(m.kickoff_at) }}</div>
            </div>
          </div>
          <button
            @click="pick(m)"
            class="mt-2 bg-cf-red hover:bg-red-700 text-white font-bold py-2 rounded-md uppercase tracking-wider text-sm"
          >
            Activar este partido
          </button>
        </div>
      </div>
    </main>
  </div>
</template>

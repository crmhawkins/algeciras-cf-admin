<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useMatchStore } from '../stores/match';
import { validarQR, type ValidationResult } from '../services/api';
import { QRScanner, extractToken } from '../services/scanner';
import ResultBanner from '../components/ResultBanner.vue';

const router = useRouter();
const matchStore = useMatchStore();

const videoEl = ref<HTMLVideoElement | null>(null);
const scanner = new QRScanner();

const result = ref<ValidationResult | null>(null);
const offlineBanner = ref(false);
const cameraError = ref('');
const validating = ref(false);
const lastToken = ref('');
const lastTokenAt = ref(0);

let bannerTimer: number | null = null;

// Audio feedback con WebAudio (sin assets externos)
let audioCtx: AudioContext | null = null;
function ensureAudio() {
  if (!audioCtx) {
    try {
      audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
    } catch {
      audioCtx = null;
    }
  }
  return audioCtx;
}

function beep(ok: boolean) {
  const ctx = ensureAudio();
  if (!ctx) return;
  const osc = ctx.createOscillator();
  const gain = ctx.createGain();
  osc.connect(gain);
  gain.connect(ctx.destination);
  osc.type = ok ? 'sine' : 'square';
  osc.frequency.value = ok ? 880 : 200;
  gain.gain.setValueAtTime(0.001, ctx.currentTime);
  gain.gain.exponentialRampToValueAtTime(0.4, ctx.currentTime + 0.02);
  gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (ok ? 0.18 : 0.45));
  osc.start();
  osc.stop(ctx.currentTime + (ok ? 0.2 : 0.5));
}

async function onDecode(text: string) {
  if (validating.value) return;
  const token = extractToken(text);
  if (!token) return;

  // dedup: ignorar mismo token < 4s
  const now = Date.now();
  if (token === lastToken.value && now - lastTokenAt.value < 4000) return;
  lastToken.value = token;
  lastTokenAt.value = now;

  scanner.pause();
  validating.value = true;

  if (!navigator.onLine) {
    offlineBanner.value = true;
    scheduleResume();
    validating.value = false;
    return;
  }

  try {
    const res = await validarQR({
      token,
      match_id: matchStore.matchId!,
      gate_id: matchStore.gateId || undefined
    });
    result.value = res;
    beep(res.valid === true);
  } catch {
    result.value = {
      valid: false,
      reason: 'client_error',
      message: 'Error inesperado'
    };
    beep(false);
  } finally {
    validating.value = false;
    scheduleResume();
  }
}

function scheduleResume(ms = 3000) {
  if (bannerTimer) window.clearTimeout(bannerTimer);
  bannerTimer = window.setTimeout(() => {
    result.value = null;
    offlineBanner.value = false;
    scanner.resume();
  }, ms);
}

async function startCamera() {
  cameraError.value = '';
  if (!videoEl.value) return;
  try {
    await scanner.start(
      videoEl.value,
      onDecode,
      (err) => {
        console.warn('[scanner]', err);
      }
    );
  } catch (e: any) {
    cameraError.value =
      e?.name === 'NotAllowedError'
        ? 'Acceso a camara denegado. Permite el acceso en los ajustes del navegador.'
        : 'No se pudo iniciar la camara: ' + (e?.message || e);
  }
}

onMounted(startCamera);
onBeforeUnmount(() => {
  if (bannerTimer) window.clearTimeout(bannerTimer);
  scanner.stop();
});
</script>

<template>
  <div class="fixed inset-0 bg-black overflow-hidden">
    <video
      ref="videoEl"
      class="absolute inset-0 w-full h-full object-cover"
      autoplay
      playsinline
      muted
    />

    <!-- overlay -->
    <div class="absolute inset-0 pointer-events-none flex flex-col">
      <!-- top bar -->
      <div class="bg-black/60 backdrop-blur-sm pointer-events-auto px-4 py-2 flex justify-between items-center">
        <button
          @click="router.replace({ name: 'matches' })"
          class="text-white/80 hover:text-white text-sm"
        >
          &larr; Cambiar partido
        </button>
        <div class="text-white text-xs truncate max-w-[180px]">
          {{ matchStore.matchLabel }}
        </div>
        <button
          @click="router.push({ name: 'stats' })"
          class="bg-cf-red text-white text-xs font-bold uppercase px-3 py-1 rounded-md"
        >
          Stats
        </button>
      </div>

      <!-- centered viewfinder -->
      <div class="flex-1 flex items-center justify-center">
        <div class="relative w-72 h-72 max-w-[80vw] max-h-[80vw]">
          <div class="absolute inset-0 border-4 border-green-400 rounded-2xl opacity-80" />
          <div class="absolute -top-1 -left-1 w-10 h-10 border-t-4 border-l-4 border-cf-red rounded-tl-2xl" />
          <div class="absolute -top-1 -right-1 w-10 h-10 border-t-4 border-r-4 border-cf-red rounded-tr-2xl" />
          <div class="absolute -bottom-1 -left-1 w-10 h-10 border-b-4 border-l-4 border-cf-red rounded-bl-2xl" />
          <div class="absolute -bottom-1 -right-1 w-10 h-10 border-b-4 border-r-4 border-cf-red rounded-br-2xl" />
        </div>
      </div>

      <!-- bottom -->
      <div class="bg-black/60 backdrop-blur-sm pointer-events-auto px-4 py-4 flex flex-col items-center gap-3">
        <div class="text-white text-sm text-center">
          Apunta al codigo QR del aficionado
        </div>
        <button
          @click="router.push({ name: 'manual' })"
          class="bg-neutral-800/80 hover:bg-neutral-700 text-white text-sm font-medium px-4 py-2 rounded-md"
        >
          Introducir token a mano
        </button>
      </div>
    </div>

    <!-- camera error -->
    <div
      v-if="cameraError"
      class="absolute inset-0 bg-neutral-950/95 flex items-center justify-center p-6 text-center"
    >
      <div>
        <div class="text-red-400 text-xl font-bold mb-3">Error de camara</div>
        <p class="text-neutral-300 mb-6">{{ cameraError }}</p>
        <button
          @click="startCamera"
          class="bg-cf-red text-white font-bold px-6 py-3 rounded-md uppercase"
        >
          Reintentar
        </button>
        <button
          @click="router.push({ name: 'manual' })"
          class="block mx-auto mt-4 text-neutral-400 underline"
        >
          Usar modo manual
        </button>
      </div>
    </div>

    <ResultBanner :result="result" :offline="offlineBanner" />
  </div>
</template>

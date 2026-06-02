import { defineStore } from 'pinia';

const STORAGE_KEY = 'aforo_cf_match';

interface MatchState {
  matchId: number | null;
  matchLabel: string;
  gateId: string;
  hydrated: boolean;
}

export const useMatchStore = defineStore('match', {
  state: (): MatchState => ({
    matchId: null,
    matchLabel: '',
    gateId: '',
    hydrated: false
  }),
  actions: {
    hydrate() {
      if (this.hydrated) return;
      this.hydrated = true;
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        const obj = JSON.parse(raw);
        this.matchId = obj.matchId ?? null;
        this.matchLabel = obj.matchLabel || '';
        this.gateId = obj.gateId || '';
      } catch {
        // ignore
      }
    },
    setMatch(id: number, label: string, gateId = '') {
      this.matchId = id;
      this.matchLabel = label;
      this.gateId = gateId;
      this.persist();
    },
    clear() {
      this.matchId = null;
      this.matchLabel = '';
      this.gateId = '';
      localStorage.removeItem(STORAGE_KEY);
    },
    persist() {
      localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
          matchId: this.matchId,
          matchLabel: this.matchLabel,
          gateId: this.gateId
        })
      );
    }
  }
});

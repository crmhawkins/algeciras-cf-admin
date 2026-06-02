import { defineStore } from 'pinia';

const HARDCODED_PIN = '12345678';
const STORAGE_KEY = 'aforo_cf_auth';

interface AuthState {
  operatorName: string;
  token: string;
  hydrated: boolean;
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    operatorName: '',
    token: '',
    hydrated: false
  }),
  getters: {
    isAuthenticated: (s) => !!s.token && !!s.operatorName
  },
  actions: {
    hydrate() {
      if (this.hydrated) return;
      this.hydrated = true;
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        const obj = JSON.parse(raw);
        this.operatorName = obj.operatorName || '';
        this.token = obj.token || '';
      } catch {
        // ignore
      }
    },
    login(operatorName: string, pin: string): boolean {
      if (pin !== HARDCODED_PIN) return false;
      if (!operatorName.trim()) return false;
      this.operatorName = operatorName.trim();
      // TODO: reemplazar por token Sanctum real
      this.token = `local-${Date.now()}`;
      localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({ operatorName: this.operatorName, token: this.token })
      );
      return true;
    },
    logout() {
      this.operatorName = '';
      this.token = '';
      localStorage.removeItem(STORAGE_KEY);
    }
  }
});

import { defineStore } from 'pinia';
import axios, { AxiosError } from 'axios';

const API_BASE = import.meta.env.VITE_API_BASE || 'https://algecirascf.hawkins.es';
const STORAGE_KEY = 'acf_operator_token';

interface StoredAuth {
  token: string;
  operatorName: string;
  email: string;
}

interface AuthState {
  operatorName: string;
  email: string;
  token: string;
  hydrated: boolean;
  loading: boolean;
  error: string;
}

interface LoginResponse {
  token: string;
  user: { name: string; email: string };
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    operatorName: '',
    email: '',
    token: '',
    hydrated: false,
    loading: false,
    error: ''
  }),
  getters: {
    isAuthenticated: (s) => !!s.token && !!s.email
  },
  actions: {
    hydrate() {
      if (this.hydrated) return;
      this.hydrated = true;
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        const obj = JSON.parse(raw) as StoredAuth;
        this.token = obj.token || '';
        this.operatorName = obj.operatorName || '';
        this.email = obj.email || '';
      } catch {
        // ignore
      }
    },
    /**
     * Devuelve cabeceras de auth para incluir en peticiones a la API.
     * Útil para llamadas hechas con fetch en lugar de la instancia axios.
     */
    getAuthHeader(): Record<string, string> {
      return this.token ? { Authorization: `Bearer ${this.token}` } : {};
    },
    /**
     * Login real contra el backend (`POST /api/operator/login`).
     * Backend responde con `{ token, user: { name, email } }` y emite
     * un token Sanctum con ability `scope:operator`.
     */
    async login(email: string, password: string): Promise<boolean> {
      this.error = '';
      this.loading = true;
      try {
        const { data } = await axios.post<LoginResponse>(
          `${API_BASE}/api/operator/login`,
          { email: email.trim(), password },
          { headers: { Accept: 'application/json' }, timeout: 10000 }
        );
        if (!data?.token) {
          this.error = 'Respuesta inválida del servidor';
          return false;
        }
        this.token = data.token;
        this.operatorName = data.user?.name || '';
        this.email = data.user?.email || email.trim();
        localStorage.setItem(
          STORAGE_KEY,
          JSON.stringify({
            token: this.token,
            operatorName: this.operatorName,
            email: this.email
          } as StoredAuth)
        );
        return true;
      } catch (err) {
        const axErr = err as AxiosError<{ message?: string }>;
        if (axErr.response?.status === 401 || axErr.response?.status === 403) {
          this.error =
            axErr.response.data?.message ||
            'Credenciales inválidas o usuario no autorizado';
        } else if (!navigator.onLine) {
          this.error = 'Sin conexión a internet';
        } else {
          this.error = 'Error de conexión, inténtalo de nuevo';
        }
        return false;
      } finally {
        this.loading = false;
      }
    },
    logout() {
      this.operatorName = '';
      this.email = '';
      this.token = '';
      this.error = '';
      localStorage.removeItem(STORAGE_KEY);
      // Redirect a /login — import dinámico para evitar ciclo con router.
      import('../router').then((m) => {
        try {
          m.default.replace({ name: 'login' });
        } catch {
          // si no estamos en contexto de router, lo ignoramos
        }
      });
    }
  }
});

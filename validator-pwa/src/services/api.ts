import axios, { AxiosError } from 'axios';
import { useAuthStore } from '../stores/auth';

const API_BASE = import.meta.env.VITE_API_BASE || 'https://algecirascf.hawkins.es';

export const api = axios.create({
  baseURL: API_BASE,
  timeout: 10000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json'
  }
});

api.interceptors.request.use((cfg) => {
  try {
    const auth = useAuthStore();
    if (auth.token) {
      cfg.headers.Authorization = `Bearer ${auth.token}`;
    }
    if (auth.operatorName) {
      cfg.headers['X-Operator-Name'] = auth.operatorName;
    }
  } catch {
    // pinia may not be ready in some contexts; ignore
  }
  return cfg;
});

// Si el backend rechaza el token (caducado, revocado, scope inválido),
// limpiamos credenciales locales y redirigimos a /login para forzar
// reautenticación del operador.
api.interceptors.response.use(
  (resp) => resp,
  (err: AxiosError) => {
    const status = err.response?.status;
    if (status === 401 || status === 403) {
      try {
        const auth = useAuthStore();
        if (auth.isAuthenticated) {
          auth.logout();
        }
      } catch {
        // pinia / router may not be available; ignore
      }
    }
    return Promise.reject(err);
  }
);

export interface ValidationOk {
  valid: true;
  type: 'abono' | 'entrada';
  attendance_id: number;
  ticket: {
    id: number;
    customer_name: string;
    holder_dni?: string;
    zone?: { name: string };
    fila?: string | number;
    asiento?: string | number;
    match_label?: string;
    type_label?: string;
  };
}

export interface ValidationKo {
  valid: false;
  reason: string;
  message: string;
}

export type ValidationResult = ValidationOk | ValidationKo;

export async function validarQR(payload: {
  token: string;
  match_id: number;
  gate_id?: string;
}): Promise<ValidationResult> {
  try {
    const { data } = await api.post<ValidationResult>('/api/validar-qr', payload);
    return data;
  } catch (err) {
    const axErr = err as AxiosError<ValidationResult>;
    if (axErr.response?.data && typeof axErr.response.data === 'object') {
      const d = axErr.response.data;
      if ('valid' in d) return d;
    }
    if (!navigator.onLine) {
      return {
        valid: false,
        reason: 'offline',
        message: 'Sin conexion - reintenta cuando vuelva el internet'
      };
    }
    return {
      valid: false,
      reason: 'network_error',
      message: 'Error de red, intentalo otra vez'
    };
  }
}

export interface Match {
  id: number;
  kickoff_at: string;
  opponent?: string;
  opponent_name?: string;
  competition?: string;
  status?: string;
  venue?: string;
  label?: string;
}

export async function fetchPartidos(): Promise<Match[]> {
  const { data } = await api.get<Match[] | { data: Match[] }>('/api/partidos');
  return Array.isArray(data) ? data : data.data || [];
}

export interface MatchStats {
  match_id: number;
  match_label?: string;
  entered: number;
  capacity: number;
  sectors: Array<{
    sector_id?: number;
    sector_name: string;
    entered: number;
    capacity: number;
  }>;
}

export async function fetchMatchStats(matchId: number): Promise<MatchStats> {
  const { data } = await api.get<MatchStats>(`/api/admin/matches/${matchId}/stats`);
  return data;
}

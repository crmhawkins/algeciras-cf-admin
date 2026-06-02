import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { useMatchStore } from './stores/match';

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('./views/LoginView.vue')
  },
  {
    path: '/matches',
    name: 'matches',
    component: () => import('./views/MatchSelectorView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/scan',
    name: 'scan',
    component: () => import('./views/ScanView.vue'),
    meta: { requiresAuth: true, requiresMatch: true }
  },
  {
    path: '/manual',
    name: 'manual',
    component: () => import('./views/ManualView.vue'),
    meta: { requiresAuth: true, requiresMatch: true }
  },
  {
    path: '/stats',
    name: 'stats',
    component: () => import('./views/StatsView.vue'),
    meta: { requiresAuth: true, requiresMatch: true }
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

router.beforeEach((to) => {
  const auth = useAuthStore();
  auth.hydrate();
  const match = useMatchStore();
  match.hydrate();

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' };
  }
  if (to.meta.requiresMatch && !match.matchId) {
    return { name: 'matches' };
  }
});

export default router;

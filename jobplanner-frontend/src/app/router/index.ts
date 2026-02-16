import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/auth.store'

const routes: RouteRecordRaw[] = [
  {
    path: '/auth',
    children: [
      {
        path: 'login',
        name: 'Login',
        component: () => import('@/features/auth/views/LoginView.vue'),
        meta: { requiresAuth: false },
      },
      {
        path: 'register',
        name: 'Register',
        component: () => import('@/features/auth/views/RegisterView.vue'),
        meta: { requiresAuth: false },
      },
    ],
  },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/features/dashboard/views/DashboardView.vue'),
      },
      {
        path: 'applications',
        name: 'Applications',
        component: () => import('@/features/applications/views/ApplicationsKanbanView.vue'),
      },
      {
        path: 'applications/:id',
        name: 'ApplicationDetail',
        component: () => import('@/features/applications/views/ApplicationDetailView.vue'),
      },
      {
        path: 'job-offers',
        name: 'JobOffers',
        component: () => import('@/features/job-offers/views/JobOffersView.vue'),
      },
      {
        path: 'interviews',
        name: 'Interviews',
        component: () => import('@/features/interviews/views/InterviewsView.vue'),
      },
      {
        path: 'emails',
        name: 'Emails',
        component: () => import('@/features/emails/views/EmailsView.vue'),
      },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('@/features/settings/views/SettingsView.vue'),
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/features/auth/views/LoginView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Auth guard
router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()
  
  // Try to restore session from localStorage
  await authStore.initialize()
  
  const requiresAuth = to.meta.requiresAuth !== false

  if (requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
  } else if (to.name === 'Login' && authStore.isAuthenticated) {
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router

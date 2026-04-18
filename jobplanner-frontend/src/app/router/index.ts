import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/auth.store'
import { useNotificationsStore } from '@/features/notifications/stores/notifications.store'

const APP_TITLE_PREFIX = 'JP'
const DEFAULT_TITLE = 'Career Atelier'

const setDocumentTitle = (title?: string) => {
  document.title = title ? `${APP_TITLE_PREFIX} - ${title}` : `${APP_TITLE_PREFIX} - ${DEFAULT_TITLE}`
}

const routes: RouteRecordRaw[] = [
  {
    path: '/auth',
    children: [
      {
        path: 'login',
        name: 'Login',
        component: () => import('@/features/auth/views/LoginView.vue'),
        meta: { requiresAuth: false, title: 'Login' },
      },
      {
        path: 'register',
        name: 'Register',
        component: () => import('@/features/auth/views/RegisterView.vue'),
        meta: { requiresAuth: false, title: 'Register' },
      },
      {
        path: 'google/callback',
        name: 'GoogleCallback',
        component: () => import('@/features/settings/views/GoogleCallbackView.vue'),
        meta: { requiresAuth: false, title: 'Google Callback' },
      },
      {
        path: 'microsoft/callback',
        name: 'MicrosoftCallback',
        component: () => import('@/features/settings/views/MicrosoftCallbackView.vue'),
        meta: { requiresAuth: false, title: 'Microsoft Callback' },
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
        meta: { title: 'Dashboard' },
      },
      {
        path: 'applications',
        name: 'Applications',
        component: () => import('@/features/applications/views/ApplicationsKanbanView.vue'),
        meta: { title: 'Applications' },
      },
      {
        path: 'applications/:id',
        name: 'ApplicationDetail',
        component: () => import('@/features/applications/views/ApplicationDetailView.vue'),
        meta: { title: 'Application' },
      },
      {
        path: 'job-offers',
        name: 'JobOffers',
        component: () => import('@/features/job-offers/views/JobOffersView.vue'),
        meta: { title: 'Job Offers' },
      },
      {
        path: 'interviews',
        name: 'Interviews',
        component: () => import('@/features/interviews/views/InterviewsView.vue'),
        meta: { title: 'Interviews' },
      },
      {
        path: 'emails',
        name: 'Emails',
        component: () => import('@/features/emails/views/EmailsView.vue'),
        meta: { title: 'Emails' },
      },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('@/features/settings/views/SettingsView.vue'),
        meta: { title: 'Settings' },
      },

    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/features/auth/views/LoginView.vue'),
    meta: { requiresAuth: false, title: 'Not Found' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()
  
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

router.afterEach((to) => {
  setDocumentTitle(typeof to.meta.title === 'string' ? to.meta.title : undefined)
})

window.addEventListener('auth:unauthorized', () => {
  const authStore = useAuthStore()
  const notificationsStore = useNotificationsStore()

  notificationsStore.stop()
  authStore.logout()
  router.push({ name: 'Login' })
})

export default router

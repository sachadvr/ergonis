<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/auth.store'
import { Button } from '@/components/ui/button'
import NotificationPopover from '@/features/notifications/components/NotificationPopover.vue'
import { 
  LayoutDashboard, 
  Briefcase, 
  FileText, 
  Calendar, 
  Mail, 
  Settings,
  LogOut,
  User,
  Menu,
} from 'lucide-vue-next'
import logo_icon from '/logo_icon.png'
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const mainRef = ref<HTMLElement | null>(null)
const showMobileNav = ref(true)

let lastScrollTop = 0
let scrollTimeout: number | undefined

// Logo path is /logo.png (served from public folder)

const navItems = [
  { name: 'Dashboard', path: '/', icon: LayoutDashboard },
  { name: 'Applications', path: '/applications', icon: Briefcase },
  { name: 'Job Offers', path: '/job-offers', icon: FileText },
  { name: 'Interviews', path: '/interviews', icon: Calendar },
  { name: 'Emails', path: '/emails', icon: Mail },
  { name: 'Settings', path: '/settings', icon: Settings },
]

const navMode = ref<'compact' | 'sidebar'>(
  typeof window !== 'undefined' && window.localStorage.getItem('jobplanner-nav-mode') === 'sidebar'
    ? 'sidebar'
    : 'compact',
)

const isSidebarMode = computed(() => navMode.value === 'sidebar')

const toggleNavMode = () => {
  navMode.value = navMode.value === 'sidebar' ? 'compact' : 'sidebar'
  localStorage.setItem('jobplanner-nav-mode', navMode.value)
}

const handleLogout = () => {
  authStore.logout()
  router.push({ name: 'Login' })
}

const updateMobileNavVisibility = () => {
  const mainElement = mainRef.value

  if (!mainElement) {
    return
  }

  const currentScrollTop = mainElement.scrollTop

  if (currentScrollTop <= 8) {
    showMobileNav.value = true
  } else if (currentScrollTop > lastScrollTop) {
    showMobileNav.value = false
  } else if (currentScrollTop < lastScrollTop) {
    showMobileNav.value = true
  }

  lastScrollTop = currentScrollTop

  window.clearTimeout(scrollTimeout)
  scrollTimeout = window.setTimeout(() => {
    if ((mainRef.value?.scrollTop ?? 0) <= 8) {
      showMobileNav.value = true
    }
  }, 120)
}

onMounted(() => {
  const mainElement = mainRef.value

  if (!mainElement) {
    return
  }

  lastScrollTop = mainElement.scrollTop
  mainElement.addEventListener('scroll', updateMobileNavVisibility, { passive: true })
})

onBeforeUnmount(() => {
  mainRef.value?.removeEventListener('scroll', updateMobileNavVisibility)

  window.clearTimeout(scrollTimeout)
})
</script>

<template>
  <div class="flex h-[100dvh] bg-background text-foreground">
    <!-- Sidebar -->
    <aside
      class="app-shell paper-panel hidden w-52 flex-col overflow-hidden"
      :class="isSidebarMode ? 'lg:flex' : 'lg:hidden'"
    >
      <div class="border-b border-border/70 px-7 py-7">
        <p class="section-kicker mb-3">Career Atelier</p>
        <h1 class="display-title text-3xl font-semibold text-foreground">
          <img src="/logo_dark.png" alt="JobPlanner" class="h-10 w-auto rounded-sm" />
        </h1>
        <p class="mt-2 text-sm text-muted-foreground">A calmer command center for your search.</p>
      </div>
      
      <nav class="space-y-2 px-4 py-5 h-full overflow-y-auto pb-[90px]">
        <RouterLink
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="relative flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all duration-200 hover:bg-primary-light"
          :class="{
            'bg-primary-light text-primary shadow-sm': route.path === item.path || (item.path !== '/' && route.path.startsWith(item.path)),
            'text-muted-foreground hover:text-foreground': route.path !== item.path && !(item.path !== '/' && route.path.startsWith(item.path)),
          }"
        >
          <span
            class="absolute left-0 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full transition-all duration-200"
            :class="route.path === item.path || (item.path !== '/' && route.path.startsWith(item.path))
              ? 'bg-primary opacity-100'
              : 'bg-transparent opacity-0'"
          />
          <component :is="item.icon" :size="18" />
          {{ item.name }}
        </RouterLink>
      </nav>

      <!-- User section at bottom -->
      <div class="fixed bottom-0 left-0 right-0 backdrop-blur-sm border-t border-border/70">
        <div class="rounded-[1.5rem]">
          <div class="flex items-center gap-3 rounded-xl border border-border/80 bg-card/90 px-1 py-2 cursor-pointer"  @click="handleLogout">
          <div class="flex h-10 w-10 items-center justify-center">
            <User :size="16" />
          </div>
          <div class="flex-1 text-xs">
            <div class="font-medium text-foreground ">{{ authStore.user?.email || 'User' }}</div>
            <div class="text-xs text-muted-foreground">Personal workspace</div>
          </div>
          <Button variant="ghost" size="icon" title="Logout">
            <LogOut :size="16" />
          </Button>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div class="app-shell paper-panel flex flex-1 flex-col overflow-hidden rounded-[2rem] border border-border/70">
      <!-- Topbar -->
      <header class="sticky top-0 z-30 border-b border-border/70 bg-background/90 px-6 py-4 backdrop-blur-sm lg:static lg:bg-transparent lg:px-8 lg:py-5 lg:backdrop-blur-none">
        <div class="flex flex-col" :class="showMobileNav ? 'gap-4' : 'gap-0'">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="section-kicker mb-2">Workspace</p>
              <h2 class="display-title text-2xl font-semibold flex items-center gap-2"><img :src="logo_icon" alt="JobPlanner" class="h-6 w-auto rounded-xs" /> {{ route.meta.title }}</h2>
            </div>
            <div class="flex items-center gap-3">
              <Button variant="ghost" size="icon" title="Toggle navigation mode" @click="toggleNavMode" class="hidden lg:block">
                <Menu :size="18" />
              </Button>
              <NotificationPopover />
            </div>
          </div>

          <nav
            class="-mx-2 flex gap-2 overflow-x-auto px-2 pb-1 transition-all duration-200 lg:flex"
            :class="[
              isSidebarMode ? 'lg:hidden' : 'lg:flex',
              showMobileNav
                ? 'max-h-16 opacity-100'
                : 'max-h-0 overflow-hidden opacity-0 pointer-events-none',
            ]"
          >
            <RouterLink
              v-for="item in navItems"
              :key="item.path"
              :to="item.path"
              class="inline-flex shrink-0 items-center gap-2 rounded-full border border-border/70 px-3 py-2 text-sm font-medium transition-colors"
              :class="{
                'bg-primary-light text-primary': route.path === item.path || (item.path !== '/' && route.path.startsWith(item.path)),
                'bg-card/80 text-muted-foreground': route.path !== item.path && !(item.path !== '/' && route.path.startsWith(item.path)),
              }"
            >
              <component :is="item.icon" :size="16" />
              {{ item.name }}
            </RouterLink>
          </nav>
        </div>
      </header>

      <!-- Page content -->
      <main ref="mainRef" class="flex-1 overflow-auto px-6 py-6 sm:px-8 sm:py-8">
        <RouterView />
      </main>
    </div>
  </div>
</template>

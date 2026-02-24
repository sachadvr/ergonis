<script setup lang="ts">
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
  User
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Logo path is /logo.png (served from public folder)

const navItems = [
  { name: 'Dashboard', path: '/', icon: LayoutDashboard },
  { name: 'Applications', path: '/applications', icon: Briefcase },
  { name: 'Job Offers', path: '/job-offers', icon: FileText },
  { name: 'Interviews', path: '/interviews', icon: Calendar },
  { name: 'Emails', path: '/emails', icon: Mail },
  { name: 'Settings', path: '/settings', icon: Settings },
]

const handleLogout = () => {
  authStore.logout()
  router.push({ name: 'Login' })
}
</script>

<template>
  <div class="flex min-h-screen bg-[#FFF] px-3 py-3 sm:px-4 sm:py-4">
    <!-- Sidebar -->
    <aside class="app-shell paper-panel hidden w-52 flex-col overflow-hidden rounded-[2rem] border border-border/70 lg:flex">
      <div class="border-b border-border/70 px-7 py-7">
        <p class="section-kicker mb-3">Career Atelier</p>
        <h1 class="display-title text-3xl font-semibold text-foreground">
          <img src="/logo_dark.png" alt="JobPlanner" class="h-10 w-auto rounded-sm" />
        </h1>
        <p class="mt-2 text-sm text-muted-foreground">A calmer command center for your search.</p>
      </div>
      
      <nav class="space-y-2 px-4 py-5">
        <RouterLink
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="relative flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200 hover:bg-accent/60"
          :class="{
            'bg-[#161632] text-[#FFF] shadow-[0_16px_30px_rgba(27,97,103,0.16)]': route.path === item.path || (item.path !== '/' && route.path.startsWith(item.path)),
            'text-muted-foreground hover:text-foreground': route.path !== item.path && !(item.path !== '/' && route.path.startsWith(item.path)),
          }"
        >
          <span
            class="absolute left-0 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full transition-all duration-200"
            :class="route.path === item.path || (item.path !== '/' && route.path.startsWith(item.path))
              ? 'bg-primary-foreground/90 opacity-100'
              : 'bg-transparent opacity-0'"
          />
          <component :is="item.icon" :size="18" />
          {{ item.name }}
        </RouterLink>
      </nav>

      <!-- User section at bottom -->
      <div class="mt-auto border-t border-border/70 p-4">
        <div class="rounded-[1.5rem] bg-secondary/70 p-3">
          <div class="mb-2 text-xs uppercase tracking-[0.18em] text-muted-foreground">Signed in</div>
          <div class="flex items-center gap-3 rounded-xl px-1 py-2 border bg-white cursor-pointer"  @click="handleLogout">
          <div class="flex h-10 w-10 items-center justify-center  bg-primary/12 text-primary">
            <User :size="16" />
          </div>
          <div class="flex-1 text-sm">
            <div class="font-medium text-foreground">{{ authStore.user?.email || 'User' }}</div>
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
      <header class="border-b border-border/70 px-6 py-5 sm:px-8">
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="section-kicker mb-2">Workspace</p>
            <h2 class="display-title text-2xl font-semibold">{{ route.meta.title }}</h2>
          </div>
          <div class="flex items-center gap-3">
            <div class="rounded-full bg-secondary/70 px-4 py-2 text-sm text-muted-foreground">
              Focused mode
            </div>
            <NotificationPopover />
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-auto px-6 py-6 sm:px-8 sm:py-8">
        <RouterView />
      </main>
    </div>
  </div>
</template>

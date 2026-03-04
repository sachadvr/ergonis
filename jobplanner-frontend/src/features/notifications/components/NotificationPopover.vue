<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Bell, Mail, RefreshCw, X } from 'lucide-vue-next'
import { onClickOutside } from '@vueuse/core'
import { useAuthStore } from '@/features/auth/stores/auth.store'
import { useNotificationsStore } from '../stores/notifications.store'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card } from '@/components/ui/card'

const router = useRouter()
const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()

const isOpen = ref(false)
const containerRef = ref<HTMLElement | null>(null)

const notifications = computed(() => notificationsStore.sortedNotifications)
const unreadCount = computed(() => notificationsStore.unreadCount)

onClickOutside(containerRef, () => {
  isOpen.value = false
})

watch(
  () => authStore.user?.id,
  (userId) => {
    if (userId) {
      notificationsStore.start(userId)
      return
    }

    notificationsStore.stop()
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  notificationsStore.stop()
})

async function togglePopover() {
  isOpen.value = !isOpen.value
  if (isOpen.value && authStore.user?.id) {
    await notificationsStore.fetchNotifications()
  }
}

function openNotification(href: string, id: number) {
  isOpen.value = false
  void notificationsStore.markAsSeen(id)
  void router.push(href)
}

function refreshNotifications() {
  if (authStore.user?.id) {
    void notificationsStore.fetchNotifications()
  }
}
</script>

<template>
  <div ref="containerRef" class="relative">
    <Button variant="ghost" size="icon" class="relative" title="Notifications" @click="togglePopover">
      <Bell :size="18" />
      <Badge
        v-if="unreadCount > 0"
        variant="default"
        class="absolute -right-1 -top-1 h-5 min-w-5 justify-center rounded-full px-1 text-[10px] bg-red-500 text-white"
      >
        {{ unreadCount }}
      </Badge>
    </Button>

    <div v-if="isOpen" class="absolute right-0 top-full z-30 mt-3 w-[22rem] sm:w-[26rem]">
      <Card class="overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-border/70 px-4 py-4">
          <div>
            <p class="text-sm font-semibold text-foreground">Notifications</p>
            <p class="text-xs text-muted-foreground">{{ unreadCount }} unread</p>
          </div>
          <Button variant="ghost" size="icon" class="h-8 w-8" title="Refresh" @click="refreshNotifications">
            <RefreshCw :size="14" />
          </Button>
        </div>

        <div class="max-h-[24rem] overflow-auto">
          <div v-if="notificationsStore.isLoading" class="px-4 py-6 text-sm text-muted-foreground">
            Loading notifications...
          </div>
          <div v-else-if="notifications.length === 0" class="px-4 py-6 text-sm text-muted-foreground">
            No new notifications.
          </div>
          <div
            v-for="notification in notifications"
            :key="notification.id"
            role="button"
            tabindex="0"
            class="flex w-full items-start gap-3 border-b border-border/60 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-accent/40"
            :class="notification.isSeen ? 'opacity-65' : 'bg-primary/5'"
            @click="openNotification(notification.href, notification.id)"
            @keydown.enter.prevent="openNotification(notification.href, notification.id)"
            @keydown.space.prevent="openNotification(notification.href, notification.id)"
          >
            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
              <Mail :size="15" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-2">
                <p class="truncate text-sm font-medium text-foreground">{{ notification.subject }}</p>
                <span class="shrink-0 text-[11px] text-muted-foreground">
                  {{ new Date(notification.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                </span>
              </div>
              <p class="mt-1 truncate text-xs text-muted-foreground">{{ notification.sender }}</p>
              <p class="mt-1 text-xs text-muted-foreground">
                {{ notification.applicationTitle }}
              </p>
            </div>
            <button
              class="ml-1 mt-0.5 rounded-md p-1 text-muted-foreground transition-colors hover:bg-background hover:text-foreground"
              :title="notification.isSeen ? 'Already seen' : 'Mark as seen'"
              @click.stop="notificationsStore.markAsSeen(notification.id)"
            >
              <X :size="14" />
            </button>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>

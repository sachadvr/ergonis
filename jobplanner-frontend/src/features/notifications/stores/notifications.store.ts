import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { notificationsApi } from '../api/notifications.api'
import type { NotificationItem } from '@/types/models.types'
import { apiClient } from '@/lib/api/client'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'
import { MERCURE_NOTIFICATION_TOPIC_PREFIX, MERCURE_URL } from '@/lib/constants/mercure.constants'

const MAX_NOTIFICATIONS = 8

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<NotificationItem[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const sortedNotifications = computed(() => {
    return [...notifications.value].sort(
      (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime(),
    )
  })

  const unreadCount = computed(() => notifications.value.filter((notification) => !notification.isSeen).length)

  let eventSource: EventSource | null = null
  let activeUserId: number | null = null
  const mercureListeners = new Set<(payload: unknown) => void>()

  async function fetchNotifications() {
    try {
      isLoading.value = true
      error.value = null

      const response = await notificationsApi.getAll()
      const items = response.member || response['hydra:member'] || []
      notifications.value = items.slice(0, MAX_NOTIFICATIONS)
    } catch (e) {
      error.value = 'Failed to fetch notifications'
      console.error('Error fetching notifications:', e)
    } finally {
      isLoading.value = false
    }
  }

  async function ensureMercureCookie() {
    await apiClient(API_ENDPOINTS.MERCURE.TOKEN, {
      method: 'POST',
    })
  }

  function start(userId: number) {
    if (activeUserId === userId && eventSource !== null) {
      return
    }

    stop()
    activeUserId = userId
    void fetchNotifications()
    void ensureMercureCookie()
      .then(() => {
        connect(userId)
      })
      .catch((e) => {
        error.value = 'Unable to authorize live notifications'
        console.error('Error initializing Mercure subscription:', e)
      })
  }

  function connect(userId: number) {
    if (!MERCURE_URL) {
      return
    }

    const url = new URL(MERCURE_URL, window.location.origin)
    url.searchParams.append('topic', `${MERCURE_NOTIFICATION_TOPIC_PREFIX}${userId}:notifications`)

    eventSource = new EventSource(url.toString(), { withCredentials: true })
    eventSource.onmessage = (event) => {
      try {
        const payload = JSON.parse(event.data) as unknown
        mercureListeners.forEach((listener) => listener(payload))
      } catch {
        // Ignore malformed payloads.
      }
    }
    eventSource.onerror = () => {
      error.value = 'Live notifications disconnected'
    }
  }

  async function markAsSeen(id: number) {
    const current = notifications.value.find((notification) => notification.id === id)
    if (!current || current.isSeen) {
      return
    }

    await notificationsApi.markAsSeen(current)
    await fetchNotifications()
  }

  function dismiss(id: number) {
    notifications.value = notifications.value.filter((notification) => notification.id !== id)
  }

  function stop() {
    eventSource?.close()
    eventSource = null
    activeUserId = null
  }

  function subscribeMercure(listener: (payload: unknown) => void) {
    mercureListeners.add(listener)

    return () => {
      mercureListeners.delete(listener)
    }
  }

  return {
    notifications,
    sortedNotifications,
    unreadCount,
    isLoading,
    error,
    fetchNotifications,
    start,
    subscribeMercure,
    dismiss,
    markAsSeen,
    stop,
  }
})

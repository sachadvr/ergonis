import { apiClient } from '@/lib/api/client'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'
import type { NotificationItem } from '@/types/models.types'
import type { PaginatedResponse } from '@/types/api.types'

export const notificationsApi = {
  async getAll(): Promise<PaginatedResponse<NotificationItem>> {
    return apiClient(API_ENDPOINTS.NOTIFICATIONS.BASE)
  },

  async markAsSeen(notification: NotificationItem): Promise<void> {
    const endpoint =
      'imported_from_extension' === notification.type
        ? API_ENDPOINTS.APPLICATION_HISTORIES.BY_ID(String(notification.id))
        : API_ENDPOINTS.EMAILS.BY_ID(String(notification.id))

    return apiClient(endpoint, {
      method: 'PATCH',
      body: { isSeen: true },
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
    })
  },
}

import { apiClient } from '@/lib/api/client'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'
import type { NotificationItem } from '@/types/models.types'
import type { PaginatedResponse } from '@/types/api.types'

export const notificationsApi = {
  async getAll(): Promise<PaginatedResponse<NotificationItem>> {
    return apiClient(API_ENDPOINTS.NOTIFICATIONS.BASE)
  },

  async markAsSeen(id: number): Promise<NotificationItem> {
    return apiClient(API_ENDPOINTS.EMAILS.BY_ID(String(id)), {
      method: 'PATCH',
      body: { isSeen: true },
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
    })
  },
}

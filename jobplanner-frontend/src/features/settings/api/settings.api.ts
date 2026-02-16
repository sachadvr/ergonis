import type { AppConfig, FollowUpRule, UserMailboxSettings } from '@/types/models.types'
import type { PaginatedResponse } from '@/types/api.types'
import { apiClient } from '@/lib/api/client'
import type { User } from '@/types/models.types'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'

export const settingsApi = {
  async getConfig(): Promise<AppConfig> {
    return apiClient(API_ENDPOINTS.SETTINGS.CONFIG)
  },

  async getMailboxSettings(): Promise<PaginatedResponse<UserMailboxSettings>> {
    return apiClient(API_ENDPOINTS.SETTINGS.MAILBOX)
  },

  async getFollowUpRules(): Promise<PaginatedResponse<FollowUpRule>> {
    return apiClient(API_ENDPOINTS.SETTINGS.FOLLOW_UP_RULES)
  },

  async updateMailboxSettings(id: number, data: Partial<UserMailboxSettings>): Promise<UserMailboxSettings> {
    return apiClient(`${API_ENDPOINTS.SETTINGS.MAILBOX}/${id}`, {
      method: 'PATCH',
      body: data,
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
    })
  },

  async createMailboxSettings(data: Partial<UserMailboxSettings>): Promise<UserMailboxSettings> {
    return apiClient(API_ENDPOINTS.SETTINGS.MAILBOX, {
      method: 'POST',
      body: data,
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
    })
  },

  async updateFollowUpRule(id: number, data: Partial<FollowUpRule>): Promise<FollowUpRule> {
    return apiClient(`${API_ENDPOINTS.SETTINGS.FOLLOW_UP_RULES}/${id}`, {
      method: 'PUT',
      body: data,
      headers: {
        'Content-Type': 'application/ld+json',
      },
    })
  },

  async testMailboxConnection(): Promise<{ success: boolean; message: string }> {
    return apiClient(API_ENDPOINTS.SETTINGS.MAILBOX_TEST, {
      method: 'POST',
      retry: 0,
    })
  },

  async getGoogleAuthUrl(): Promise<{ url: string }> {
    return apiClient('/api/auth/google/url')
  },

  async confirmGoogleAuth(code: string): Promise<{ success: boolean; email: string; token?: string; user?: User }> {
    return apiClient('/api/auth/google/callback', {
      method: 'POST',
      body: { code },
    })
  },

  async getMicrosoftAuthUrl(): Promise<{ url: string }> {
    return apiClient('/api/auth/microsoft/url')
  },

  async confirmMicrosoftAuth(code: string): Promise<{ success: boolean; email: string; token?: string; user?: User }> {
    return apiClient('/api/auth/microsoft/callback', {
      method: 'POST',
      body: { code },
    })
  },
}

import type { AppConfig, FollowUpRule, UserMailboxSettings } from '@/types/models.types'
import type { PaginatedResponse } from '@/types/api.types'
import { apiClient } from '@/lib/api/client'
import type { User } from '@/types/models.types'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'

export const settingsApi = {
  /**
   * Get the application configuration
   */
  async getConfig(): Promise<AppConfig> {
    return apiClient(API_ENDPOINTS.SETTINGS.CONFIG)
  },

  /**
   * Get the mailbox settings
   */
  async getMailboxSettings(): Promise<PaginatedResponse<UserMailboxSettings>> {
    return apiClient(API_ENDPOINTS.SETTINGS.MAILBOX)
  },

  /**
   * Get the follow-up rules
   */
  async getFollowUpRules(): Promise<PaginatedResponse<FollowUpRule>> {
    return apiClient(API_ENDPOINTS.SETTINGS.FOLLOW_UP_RULES)
  },

  /**
   * Update the mailbox settings
   */
  async updateMailboxSettings(id: number, data: Partial<UserMailboxSettings>): Promise<UserMailboxSettings> {
    return apiClient(`${API_ENDPOINTS.SETTINGS.MAILBOX}/${id}`, {
      method: 'PATCH',
      body: data,
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
    })
  },

  /**
   * Create a new mailbox settings
   */
  async createMailboxSettings(data: Partial<UserMailboxSettings>): Promise<UserMailboxSettings> {
    return apiClient(API_ENDPOINTS.SETTINGS.MAILBOX, {
      method: 'POST',
      body: data,
      headers: {
        'Content-Type': 'application/ld+json',
      },
    })
  },

  /**
   * Update a follow-up rule
   */
  async updateFollowUpRule(id: number, data: Partial<FollowUpRule>): Promise<FollowUpRule> {
    return apiClient(`${API_ENDPOINTS.SETTINGS.FOLLOW_UP_RULES}/${id}`, {
      method: 'PUT',
      body: data,
      headers: {
        'Content-Type': 'application/ld+json',
      },
    })
  },

  /**
   * Test the mailbox connection
   */
  async testMailboxConnection(): Promise<{ success: boolean; message: string }> {
    return apiClient(API_ENDPOINTS.SETTINGS.MAILBOX_TEST, {
      method: 'POST',
      retry: 0,
    })
  },

  /**
   * Get the Google authentication URL
   */
  async getGoogleAuthUrl(): Promise<{ url: string }> {
    return apiClient('/api/auth/google/url')
  },

  /**
   * Confirm the Google authentication
   */
  async confirmGoogleAuth(code: string): Promise<{ success: boolean; email: string; token?: string; user?: User }> {
    return apiClient('/api/auth/google/callback', {
      method: 'POST',
      body: { code },
    })
  },

  /**
   * Get the Microsoft authentication URL
   */
  async getMicrosoftAuthUrl(): Promise<{ url: string }> {
    return apiClient('/api/auth/microsoft/url')
  },

  /**
   * Confirm the Microsoft authentication
   */
  async confirmMicrosoftAuth(code: string): Promise<{ success: boolean; email: string; token?: string; user?: User }> {
    return apiClient('/api/auth/microsoft/callback', {
      method: 'POST',
      retry: 0,
      body: { code },
    })
  },
}

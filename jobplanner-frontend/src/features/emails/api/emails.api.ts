import type { RecruiterEmail } from '@/types/models.types'
import type { PaginatedResponse } from '@/types/api.types'
import { apiClient } from '@/lib/api/client'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'

export const emailsApi = {
  async getAll(): Promise<PaginatedResponse<RecruiterEmail>> {
    return apiClient(API_ENDPOINTS.EMAILS.BASE)
  },

  async getById(id: string): Promise<RecruiterEmail> {
    return apiClient(API_ENDPOINTS.EMAILS.BY_ID(id))
  },
}

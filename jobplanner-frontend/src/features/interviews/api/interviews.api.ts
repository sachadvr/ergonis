import type { Interview } from '@/types/models.types'
import type { PaginatedResponse } from '@/types/api.types'
import { apiClient } from '@/lib/api/client'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'

export const interviewsApi = {
  async getAll(): Promise<PaginatedResponse<Interview>> {
    return apiClient(API_ENDPOINTS.INTERVIEWS.BASE)
  },

  async getById(id: string): Promise<Interview> {
    return apiClient(API_ENDPOINTS.INTERVIEWS.BY_ID(id))
  },
}

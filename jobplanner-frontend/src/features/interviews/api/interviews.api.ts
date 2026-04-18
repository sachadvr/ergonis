import type { CreateInterviewDto, Interview } from '@/types/models.types'
import type { PaginatedResponse } from '@/types/api.types'
import { apiClient } from '@/lib/api/client'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'

export const interviewsApi = {
  /**
   * Create a new interview
   */
  async create(data: CreateInterviewDto): Promise<Interview> {
    return apiClient(API_ENDPOINTS.INTERVIEWS.BASE, {
      method: 'POST',
      body: data,
      headers: {
        'Content-Type': 'application/ld+json',
      },
    })
  },

  /**
   * Get all interviews
   */
  async getAll(): Promise<PaginatedResponse<Interview>> {
    return apiClient(API_ENDPOINTS.INTERVIEWS.BASE)
  },

  /**
   * Get an interview by ID
   */
  async getById(id: string): Promise<Interview> {
    return apiClient(API_ENDPOINTS.INTERVIEWS.BY_ID(id))
  },
}

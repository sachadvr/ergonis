import type { JobOffer } from '@/types/models.types'
import type { PaginatedResponse } from '@/types/api.types'
import { apiClient } from '@/lib/api/client'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'

export const jobOffersApi = {
  /**
   * Get all job offers
   */
  async getAll(): Promise<PaginatedResponse<JobOffer>> {
    return apiClient(API_ENDPOINTS.JOB_OFFERS.BASE)
  },

  /**
   * Get a job offer by ID
   */
  async getById(id: string): Promise<JobOffer> {
    return apiClient(API_ENDPOINTS.JOB_OFFERS.BY_ID(id))
  },

  /**
   * Create a new job offer
   */
  async create(data: Partial<JobOffer>): Promise<JobOffer> {
    return apiClient(API_ENDPOINTS.JOB_OFFERS.BASE, {
      method: 'POST',
      body: data,
    })
  },

  /**
   * Update a job offer
   */
  async update(id: string, data: Partial<JobOffer>): Promise<JobOffer> {
    return apiClient(API_ENDPOINTS.JOB_OFFERS.BY_ID(id), {
      method: 'PATCH',
      body: data,
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
    })
  },

  /**
   * Delete a job offer
   */
  async delete(id: string): Promise<void> {
    return apiClient(API_ENDPOINTS.JOB_OFFERS.BY_ID(id), {
      method: 'DELETE',
    })
  },
}

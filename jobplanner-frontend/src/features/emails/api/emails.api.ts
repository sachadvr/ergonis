import type { RecruiterEmail } from '@/types/models.types'
import type { PaginatedResponse } from '@/types/api.types'
import { apiClient } from '@/lib/api/client'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'

export interface CreateRecruiterEmailDto {
  application: string
  sender: string
  subject: string
  body: string
  receivedAt: string
  direction?: RecruiterEmail['direction']
  isFavourite?: boolean
  isDeleted?: boolean
  isDraft?: boolean
  labels?: string[]
}

export const emailsApi = {
  /**
   * Get all recruiter emails
   */
  async getAll(): Promise<PaginatedResponse<RecruiterEmail>> {
    return apiClient(API_ENDPOINTS.EMAILS.BASE)
  },

  /**
   * Get a recruiter email by ID
   */
  async getById(id: string): Promise<RecruiterEmail> {
    return apiClient(API_ENDPOINTS.EMAILS.BY_ID(id))
  },

  /**
   * Create a new recruiter email
   */
  async create(data: CreateRecruiterEmailDto): Promise<RecruiterEmail> {
    return apiClient(API_ENDPOINTS.EMAILS.BASE, {
      method: 'POST',
      body: data,
      headers: {
        'Content-Type': 'application/ld+json',
      },
    })
  },
}

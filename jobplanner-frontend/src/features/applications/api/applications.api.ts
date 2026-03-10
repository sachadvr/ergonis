import type { Application, ApplicationCvFitStatus } from '@/types/models.types'
import type { PaginatedResponse } from '@/types/api.types'
import { apiClient } from '@/lib/api/client'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'

/**
 * Applications API
 * Handles all API calls related to job applications
 */
export const applicationsApi = {
  /**
   * Fetch all applications for the authenticated user
   */
  async getAll(params?: {
    page?: number
    itemsPerPage?: number
    status?: string
    search?: string
  }): Promise<PaginatedResponse<Application>> {
    return apiClient(API_ENDPOINTS.APPLICATIONS.BASE, {
      params,
    })
  },

  /**
   * Fetch a single application by ID
   */
  async getById(id: string): Promise<Application> {
    return apiClient(API_ENDPOINTS.APPLICATIONS.BY_ID(id))
  },

  /**
   * Create a new application
   */
  async create(data: Partial<Application>): Promise<Application> {
    return apiClient(API_ENDPOINTS.APPLICATIONS.BASE, {
      method: 'POST',
      body: data,
      headers: {
        'Content-Type': 'application/ld+json',
      },
    })
  },

  /**
   * Update an existing application
   */
  async update(id: string, data: Partial<Application>): Promise<Application> {
    return apiClient(API_ENDPOINTS.APPLICATIONS.BY_ID(id), {
      method: 'PATCH',
      body: data,
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
    })
  },

  /**
   * Delete an application
   */
  async delete(id: string): Promise<void> {
    return apiClient(API_ENDPOINTS.APPLICATIONS.BY_ID(id), {
      method: 'DELETE',
    })
  },

  /**
   * Update application status
   */
  async updateStatus(
    id: string,
    status: Application['status'],
  ): Promise<Application> {
    return apiClient(API_ENDPOINTS.APPLICATIONS.BY_ID(id), {
      method: 'PATCH',
      body: { status },
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
    })
  },

  /**
   * Analyze a PDF CV against an application
   */
  async analyzeCvFit(id: string, file: File): Promise<ApplicationCvFitStatus> {
    const formData = new FormData()
    formData.append('cv', file)

    return apiClient(`${API_ENDPOINTS.APPLICATIONS.BY_ID(id)}/cv-fit`, {
      method: 'POST',
      body: formData,
    })
  },

  /**
   * Get applications by status (for Kanban view)
   */
  async getByStatus(): Promise<Record<Application['status'], Application[]>> {
    const response = await this.getAll()
    const applications = response.member || response['hydra:member'] || []

    // Group by status
    return applications.reduce(
      (acc, app) => {
        if (!acc[app.status]) {
          acc[app.status] = []
        }
        acc[app.status].push(app)
        return acc
      },
      {} as Record<Application['status'], Application[]>,
    )
  },
}

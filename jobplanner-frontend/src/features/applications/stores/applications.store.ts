import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Application, ApplicationFormValues } from '@/types/models.types'
import { applicationsApi } from '../api/applications.api'
import { jobOffersApi } from '@/features/job-offers/api/job-offers.api'
import {
  transformApplication,
  prepareApplicationForCreate,
  prepareApplicationForUpdate,
  prepareJobOfferForApi,
} from '@/lib/utils/api-transforms'

/**
 * Applications Store
 * Manages the state of job applications
 */
export const useApplicationsStore = defineStore('applications', () => {
  // State
  const applications = ref<Application[]>([])
  const currentApplication = ref<Application | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Computed - Group applications by status for Kanban view
  const applicationsByStatus = computed(() => {
    const grouped: Record<Application['status'], Application[]> = {
      wishlist: [],
      applied: [],
      interview: [],
      offer: [],
      rejected: [],
      accepted: [],
    }

    applications.value.forEach((app) => {
      if (grouped[app.status]) {
        grouped[app.status].push(app)
      }
    })

    return grouped
  })

  // Computed - Statistics
  const stats = computed(() => ({
    total: applications.value.length,
    wishlist: applications.value.filter((a) => a.status === 'wishlist').length,
    applied: applications.value.filter((a) => a.status === 'applied').length,
    interview: applications.value.filter((a) => a.status === 'interview').length,
    offer: applications.value.filter((a) => a.status === 'offer').length,
    rejected: applications.value.filter((a) => a.status === 'rejected').length,
    accepted: applications.value.filter((a) => a.status === 'accepted').length,
  }))

  // Actions
  /**
   * Fetch all applications
   */
  async function fetchApplications() {
    try {
      isLoading.value = true
      error.value = null
      const response = await applicationsApi.getAll()
      const rawApplications = response.member || response['hydra:member'] || []
      applications.value = rawApplications.map(transformApplication)
    } catch (e) {
      error.value = 'Failed to fetch applications'
      console.error('Error fetching applications:', e)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch a single application by ID
   */
  async function fetchApplicationById(id: string) {
    try {
      isLoading.value = true
      error.value = null
      const rawApplication = await applicationsApi.getById(id)
      currentApplication.value = transformApplication(rawApplication)
    } catch (e) {
      error.value = 'Failed to fetch application'
      console.error('Error fetching application:', e)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Create a new application
   */
  async function createApplication(
    data: ApplicationFormValues,
  ) {
    try {
      isLoading.value = true
      error.value = null
      const createdJobOffer = await jobOffersApi.create(prepareJobOfferForApi(data))
      const rawApplication = await applicationsApi.create(
        prepareApplicationForCreate(`/api/job_offers/${createdJobOffer.id}`, data) as unknown as Partial<Application>,
      )
      const newApplication = transformApplication(rawApplication)
      applications.value.push(newApplication)
      return newApplication
    } catch (e) {
      error.value = 'Failed to create application'
      console.error('Error creating application:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Update an existing application
   */
  async function updateApplication(id: number, data: ApplicationFormValues, jobOfferId?: number) {
    try {
      isLoading.value = true
      error.value = null
      let updatedJobOffer = null

      if (jobOfferId !== undefined && jobOfferId !== null) {
        updatedJobOffer = await jobOffersApi.update(String(jobOfferId), prepareJobOfferForApi(data))
      }

      const rawApplication = await applicationsApi.update(
        String(id),
        prepareApplicationForUpdate(data) as unknown as Partial<Application>,
      )
      const updatedApplication = transformApplication(rawApplication)
      if (updatedJobOffer) {
        updatedApplication.jobOffer = {
          ...updatedApplication.jobOffer,
          ...updatedJobOffer,
          notes: updatedJobOffer.notes ?? updatedApplication.jobOffer.notes,
          interviewPrep: updatedJobOffer.interviewPrep ?? updatedApplication.jobOffer.interviewPrep,
        }
        updatedApplication.notes = updatedApplication.jobOffer.notes
        updatedApplication.interviewPrep = updatedApplication.jobOffer.interviewPrep
      }
      
      // Update in the list
      const index = applications.value.findIndex((a) => a.id === id)
      if (index !== -1) {
        applications.value[index] = updatedApplication
      }

      // Update current if it's the same
      if (currentApplication.value?.id === id) {
        currentApplication.value = updatedApplication
      }

      return updatedApplication
    } catch (e) {
      error.value = 'Failed to update application'
      console.error('Error updating application:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Delete an application
   */
  async function deleteApplication(id: number) {
    try {
      isLoading.value = true
      error.value = null
      await applicationsApi.delete(String(id))
      
      // Remove from list
      applications.value = applications.value.filter((a) => a.id !== id)

      // Clear current if it's the same
      if (currentApplication.value?.id === id) {
        currentApplication.value = null
      }
    } catch (e) {
      error.value = 'Failed to delete application'
      console.error('Error deleting application:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Delete an application and its linked job offer
   */
  async function deleteApplicationWithJobOffer(id: number, jobOfferId?: number) {
    try {
      isLoading.value = true
      error.value = null

      await applicationsApi.delete(String(id))

      if (jobOfferId !== undefined && jobOfferId !== null) {
        await jobOffersApi.delete(String(jobOfferId))
      }

      applications.value = applications.value.filter((a) => a.id !== id)

      if (currentApplication.value?.id === id) {
        currentApplication.value = null
      }
    } catch (e) {
      error.value = 'Failed to delete application'
      console.error('Error deleting application:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Update application status (optimistic update)
   */
  async function updateApplicationStatus(id: number, status: Application['status']) {
    // Find the application
    const application = applications.value.find((a) => a.id === id)
    if (!application) return

    // Store old status for rollback
    const oldStatus = application.status

    try {
      // Optimistic update
      application.status = status

      // API call
      await applicationsApi.updateStatus(String(id), status)
    } catch (e) {
      // Rollback on error
      application.status = oldStatus
      error.value = 'Failed to update status'
      console.error('Error updating status:', e)
      throw e
    }
  }

  /**
   * Reset store
   */
  function $reset() {
    applications.value = []
    currentApplication.value = null
    isLoading.value = false
    error.value = null
  }

  return {
    // State
    applications,
    currentApplication,
    isLoading,
    error,

    // Computed
    applicationsByStatus,
    stats,

    // Actions
    fetchApplications,
    fetchApplicationById,
    createApplication,
    updateApplication,
    deleteApplication,
    deleteApplicationWithJobOffer,
    updateApplicationStatus,
    $reset,
  }
})

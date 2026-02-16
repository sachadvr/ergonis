import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { JobOffer } from '@/types/models.types'
import { jobOffersApi } from '../api/job-offers.api'

export const useJobOffersStore = defineStore('job-offers', () => {
  const jobOffers = ref<JobOffer[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const sortedJobOffers = computed(() => {
    return [...jobOffers.value].sort((a, b) => a.company.localeCompare(b.company))
  })

  async function fetchJobOffers() {
    try {
      isLoading.value = true
      error.value = null
      const response = await jobOffersApi.getAll()
      jobOffers.value = response.member || response['hydra:member'] || []
    } catch (e) {
      error.value = 'Failed to fetch job offers'
      console.error('Error fetching job offers:', e)
    } finally {
      isLoading.value = false
    }
  }

  return {
    jobOffers,
    isLoading,
    error,
    sortedJobOffers,
    fetchJobOffers,
  }
})

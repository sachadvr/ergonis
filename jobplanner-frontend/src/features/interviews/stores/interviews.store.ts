import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { Interview } from '@/types/models.types'
import { interviewsApi } from '../api/interviews.api'

export const useInterviewsStore = defineStore('interviews', () => {
  const interviews = ref<Interview[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const sortedInterviews = computed(() => {
    return [...interviews.value].sort((a, b) => {
      return new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime()
    })
  })

  async function fetchInterviews() {
    try {
      isLoading.value = true
      error.value = null
      const response = await interviewsApi.getAll()
      interviews.value = response.member || response['hydra:member'] || []
    } catch (e) {
      error.value = 'Failed to fetch interviews'
      console.error('Error fetching interviews:', e)
    } finally {
      isLoading.value = false
    }
  }

  return {
    interviews,
    isLoading,
    error,
    sortedInterviews,
    fetchInterviews,
  }
})

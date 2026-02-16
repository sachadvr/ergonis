import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { RecruiterEmail } from '@/types/models.types'
import { emailsApi } from '../api/emails.api'

export const useEmailsStore = defineStore('emails', () => {
  const emails = ref<RecruiterEmail[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const sortedEmails = computed(() => {
    return [...emails.value].sort((a, b) => {
      return new Date(b.receivedAt).getTime() - new Date(a.receivedAt).getTime()
    })
  })

  async function fetchEmails() {
    try {
      isLoading.value = true
      error.value = null
      const response = await emailsApi.getAll()
      emails.value = response.member || response['hydra:member'] || []
    } catch (e) {
      error.value = 'Failed to fetch emails'
      console.error('Error fetching emails:', e)
    } finally {
      isLoading.value = false
    }
  }

  return {
    emails,
    isLoading,
    error,
    sortedEmails,
    fetchEmails,
  }
})

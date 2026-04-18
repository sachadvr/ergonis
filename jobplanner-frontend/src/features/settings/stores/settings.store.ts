import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { AppConfig, FollowUpRule, UserMailboxSettings } from '@/types/models.types'
import { settingsApi } from '../api/settings.api'

export const useSettingsStore = defineStore('settings', () => {
  const config = ref<AppConfig | null>(null)
  const mailboxSettings = ref<UserMailboxSettings[]>([])
  const followUpRules = ref<FollowUpRule[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function fetchSettings() {
    try {
      isLoading.value = true
      error.value = null

      const [configResponse, mailboxResponse, followUpResponse] = await Promise.all([
        settingsApi.getConfig(),
        settingsApi.getMailboxSettings(),
        settingsApi.getFollowUpRules(),
      ])

      config.value = configResponse
      mailboxSettings.value = mailboxResponse.member || mailboxResponse['hydra:member'] || []
      followUpRules.value = followUpResponse.member || followUpResponse['hydra:member'] || []
    } catch (e) {
      error.value = 'Failed to fetch settings'
      console.error('Error fetching settings:', e)
    } finally {
      isLoading.value = false
    }
  }

  async function saveMailboxSettings(data: Partial<UserMailboxSettings>) {
    try {
      error.value = null

      let saved: UserMailboxSettings
      const current = mailboxSettings.value[0]

      if (current?.id) {
        saved = await settingsApi.updateMailboxSettings(current.id, data)
        mailboxSettings.value = [saved]
      } else {
        saved = await settingsApi.createMailboxSettings(data)
        mailboxSettings.value = [saved]
      }

      return saved
    } catch (e) {
      error.value = 'Failed to save mailbox settings'
      console.error('Error saving mailbox settings:', e)
      throw e
    } finally {
    }
  }

  async function saveFollowUpRule(id: number, data: Partial<FollowUpRule>) {
    try {
      error.value = null

      const saved = await settingsApi.updateFollowUpRule(id, data)
      const index = followUpRules.value.findIndex((rule) => rule.id === id)
      if (index !== -1) {
        followUpRules.value[index] = saved
      }

      return saved
    } catch (e) {
      error.value = 'Failed to save follow-up rule'
      console.error('Error saving follow-up rule:', e)
      throw e
    } finally {
    }
  }

  async function testMailboxConnection(): Promise<{ success: boolean; message: string }> {
    try {
      error.value = null
      return await settingsApi.testMailboxConnection()
    } catch (e) {
      const err = e as { success?: boolean; message?: string }
      const result = { 
        success: err.success ?? false, 
        message: err.message ?? 'Failed to test connection' 
      }
      return result
    }
  }

  return {
    config,
    mailboxSettings,
    followUpRules,
    isLoading,
    error,
    fetchSettings,
    saveMailboxSettings,
    saveFollowUpRule,
    testMailboxConnection,
  }
})

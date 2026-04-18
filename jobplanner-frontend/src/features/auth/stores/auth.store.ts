import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/models.types'
import { apiClient } from '@/lib/api/client'
import { API_ENDPOINTS } from '@/lib/constants/api.constants'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoading = ref(false)
  const error = ref<Error | null>(null)
  const isInitialized = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const login = async (email: string, password: string) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await apiClient<{ token: string }>(API_ENDPOINTS.AUTH.LOGIN, {
        method: 'POST',
        body: { email, password },
      })

      token.value = response.token
      localStorage.setItem('auth_token', response.token)

      await fetchMe()
    } catch (e) {
      error.value = e as Error
      throw e
    } finally {
      isLoading.value = false
    }
  }

  const register = async (email: string, password: string) => {
    try {
      isLoading.value = true
      error.value = null

      await apiClient(API_ENDPOINTS.AUTH.REGISTER, {
        method: 'POST',
        body: { email, password },
      })

      await login(email, password)
    } catch (e) {
      error.value = e as Error
      throw e
    } finally {
      isLoading.value = false
    }
  }

  const fetchMe = async () => {
    try {
      const userData = await apiClient<User>(API_ENDPOINTS.AUTH.ME)
      user.value = userData
    } catch (e) {
      error.value = e as Error
      throw e
    }
  }

  const initialize = async () => {
    if (isInitialized.value) return
    
    if (token.value && !user.value) {
      try {
        await fetchMe()
      } catch (e) {
        logout()
      }
    }
    
    isInitialized.value = true
  }

  const logout = () => {
    user.value = null
    token.value = null
    isInitialized.value = false
    localStorage.removeItem('auth_token')
  }

  const completeSocialLogin = async (tokenValue: string, userData: User) => {
    token.value = tokenValue
    user.value = userData
    localStorage.setItem('auth_token', tokenValue)
    localStorage.setItem('auth_user', JSON.stringify(userData))
  }

  const $reset = () => {
    user.value = null
    token.value = null
    isLoading.value = false
    error.value = null
    isInitialized.value = false
  }

  return {
    user,
    token,
    isLoading,
    error,

    isAuthenticated,

    login,
    register,
    fetchMe,
    initialize,
    logout,
    completeSocialLogin,
    $reset,
  }
})

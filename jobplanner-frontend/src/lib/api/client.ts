import { ofetch } from 'ofetch'
import { API_BASE_URL, API_TIMEOUT } from '../constants/api.constants'

export const apiClient = ofetch.create({
  baseURL: API_BASE_URL,
  timeout: API_TIMEOUT,
  retry: 2,
  retryDelay: 500,

  onRequest({ options }) {
    const token = localStorage.getItem('auth_token')
    if (token) {
      options.headers = new Headers(options.headers)
      options.headers.set('Authorization', `Bearer ${token}`)
      options.headers.set('Accept', 'application/ld+json')
    }
  },

  async onResponseError({ response }) {
    if (response.status === 401) {
      localStorage.removeItem('auth_token')
      window.location.href = '/auth/login'
    }
  },
})

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
      if (options.method === 'POST' || options.method === 'PUT' || options.method === 'PATCH') {
        if (!options.headers.get('Content-Type')) {
          options.headers.set('Content-Type', 'application/ld+json')
        }
      }
    }
  },

  async onResponseError({ response }) {
    if (response.status === 401) {
      localStorage.removeItem('auth_token')
      // Use a custom event so the Vue router handles the redirect without a full page reload
      window.dispatchEvent(new CustomEvent('auth:unauthorized'))
      return
    }

    const body = await response.clone().json().catch(() => ({}))
    const error = new Error(body.detail || 'Request failed')
    ;(error as { response?: typeof response }).response = response
    throw error
  },
})

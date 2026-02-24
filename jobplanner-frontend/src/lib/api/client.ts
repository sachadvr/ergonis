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
    // ofetch already parses the body and puts it in _data for errors
    const body = response._data || (response.bodyUsed ? {} : await response.clone().json().catch(() => ({})))
    
    if (response.status === 401 || body.code === 401) {
      localStorage.removeItem('auth_token')
      // Use a custom event so the Vue router handles the redirect without a full page reload
      window.dispatchEvent(new CustomEvent('auth:unauthorized'))
      
      const error = new Error(body.message || body.detail || body.error || 'Unauthorized')
      ;(error as any).response = response
      ;(error as any).data = body
      throw error
    }

    const error = new Error(body.detail || body.error || body.message || 'Request failed')
    ;(error as any).response = response
    ;(error as any).data = body
    throw error
  },
})

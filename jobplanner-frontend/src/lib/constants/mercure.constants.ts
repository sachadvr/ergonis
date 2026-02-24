const useProxy = import.meta.env.VITE_USE_PROXY === 'true'

export const MERCURE_URL =
  import.meta.env.VITE_MERCURE_URL ||
  (useProxy ? '/.well-known/mercure' : 'http://localhost:3000/.well-known/mercure')

export const MERCURE_NOTIFICATION_TOPIC_PREFIX = 'urn:jobplanner:user:'

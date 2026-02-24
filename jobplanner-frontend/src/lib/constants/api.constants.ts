const useProxy = import.meta.env.VITE_USE_PROXY === 'true'
export const API_BASE_URL = useProxy ? '' : (import.meta.env.VITE_API_URL || '')
export const API_TIMEOUT = 10000

export const API_ENDPOINTS = {
  AUTH: {
    LOGIN: '/api/login',
    REGISTER: '/api/register',
    ME: '/api/me',
  },
  APPLICATIONS: {
    BASE: '/api/applications',
    BY_ID: (id: string) => `/api/applications/${id}`,
  },
  JOB_OFFERS: {
    BASE: '/api/job_offers',
    BY_ID: (id: string) => `/api/job_offers/${id}`,
  },
  INTERVIEWS: {
    BASE: '/api/interviews',
    BY_ID: (id: string) => `/api/interviews/${id}`,
  },
  EMAILS: {
    BASE: '/api/recruiter_emails',
    BY_ID: (id: string) => `/api/recruiter_emails/${id}`,
  },
  SETTINGS: {
    CONFIG: '/api/config',
    MAILBOX: '/api/user_mailbox_settings',
    MAILBOX_TEST: '/api/mailbox/test',
    FOLLOW_UP_RULES: '/api/follow_up_rules',
  },
} as const

export const APP_NAME = 'JobPlanner'
export const APP_VERSION = '2.0.0'

export const APPLICATION_STATUSES = [
  'wishlist',
  'applied',
  'interview',
  'offer',
  'rejected',
  'accepted',
] as const

export const INTERVIEW_TYPES = ['visio', 'tel', 'presentiel'] as const

export const EMAIL_DIRECTIONS = ['INCOMING', 'OUTGOING'] as const

export const FOLLOW_UP_TEMPLATE_TYPES = [
  'follow_up',
  'thank_you',
  'spontaneous',
] as const

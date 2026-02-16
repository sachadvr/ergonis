import type {
  APPLICATION_STATUSES,
  INTERVIEW_TYPES,
  EMAIL_DIRECTIONS,
  FOLLOW_UP_TEMPLATE_TYPES,
} from '@/lib/constants/app.constants'

export type ApplicationStatus = (typeof APPLICATION_STATUSES)[number]
export type InterviewType = (typeof INTERVIEW_TYPES)[number]
export type EmailDirection = (typeof EMAIL_DIRECTIONS)[number]
export type FollowUpTemplateType = (typeof FOLLOW_UP_TEMPLATE_TYPES)[number]

export interface User {
  id: number
  email: string
  roles: string[]
  createdAt: string
}

export interface JobOffer {
  id: number
  title: string
  company: string
  url?: string
  location?: string
  notes?: string
  interviewPrep?: string
  sourceUrl?: string
  recruiterContactEmail?: string
  salaryMin?: number
  salaryMax?: number
  createdAt: string
  updatedAt?: string
}

export interface Application {
  id: number
  // Flattened fields from jobOffer for easier access
  jobTitle: string
  companyName: string
  location?: string
  url?: string
  notes?: string
  salaryMin?: number
  salaryMax?: number
  // Original nested structure
  jobOffer: JobOffer
  status: ApplicationStatus
  pipelinePosition: number
  appliedAt?: string
  lastActivityAt?: string
  createdAt?: string
  updatedAt?: string
  history?: ApplicationHistory[]
  recruiterEmails?: RecruiterEmail[]
  interviews?: Array<Interview | string>
  scheduledFollowUps?: ScheduledFollowUp[]
}

export interface CreateApplicationDto {
  jobTitle: string
  companyName: string
  location?: string
  status: ApplicationStatus
  jobUrl?: string
  salaryMin?: number
  salaryMax?: number
  notes?: string
  appliedAt?: string
}

export interface ApplicationFormValues extends CreateApplicationDto {
  pipelinePosition?: number
  recruiterContactEmail?: string
  sourceUrl?: string
  interviewPrep?: string
}

export interface ApplicationHistory {
  id: number
  actionType: string
  description?: string
  createdAt: string
}

export interface RecruiterEmail {
  id: number
  sender: string
  subject: string
  body: string
  messageId?: string
  receivedAt: string
  aiSummary?: string
  direction: EmailDirection
  isFavourite: boolean
  isDeleted: boolean
  isDraft: boolean
  labels: string[]
}

export interface Interview {
  id: number
  scheduledAt: string
  type: InterviewType
  notes?: string
  locationOrLink?: string
  contactName?: string
  reminderSent: boolean
  reminderSentAt?: string
}

export interface FollowUpRule {
  id: number
  daysWithoutReply: number
  templateType: FollowUpTemplateType
  enabled: boolean
  createdAt: string
}

export interface ScheduledFollowUp {
  id: number
  scheduledAt: string
  status: 'pending' | 'sent' | 'cancelled'
  cancelledAt?: string
  generatedContent?: string
}

export interface UserMailboxSettings {
  id: number
  imapHost: string
  imapPort: number
  imapEncryption: 'ssl' | 'tls' | 'none'
  imapUser: string
  imapPassword: string
  imapFolder: string
  smtpHost?: string
  smtpPort?: number
  smtpEncryption?: 'ssl' | 'tls' | 'none'
  smtpUser?: string
  smtpPassword?: string
  isActive: boolean
  oauthProvider?: string
  tokenExpiresAt?: string
  createdAt: string
  updatedAt?: string
}

export interface AppConfig {
  simulationMode: boolean
}

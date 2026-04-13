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
  rawContent?: string
  jobSummary?: string
  salaryMin?: number
  salaryMax?: number
  salaryCurrency?: string
  contractType?: string
  remotePolicy?: string
  details?: Record<string, unknown>
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
  interviewPrep?: string
  rawContent?: string
  jobSummary?: string
  salaryCurrency?: string
  contractType?: string
  remotePolicy?: string
  details?: Record<string, unknown>
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
  cvFitAnalysisStatus?: 'queued' | 'processing' | 'completed' | 'failed' | null
  cvFitAnalysisResult?: ApplicationCvFitAnalysis | null
  cvFitAnalysisRequestedAt?: string | null
  cvFitAnalysisCompletedAt?: string | null
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

export interface NotificationItem {
  id: number
  type: 'email_received'
  title: string
  message: string
  createdAt: string
  applicationId: number
  applicationTitle: string
  sender: string
  subject: string
  isSeen: boolean
  href: string
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

export interface CreateInterviewDto {
  application: string
  scheduledAt: string
  type?: InterviewType
  notes?: string
  locationOrLink?: string
  contactName?: string
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

export interface ApplicationCvFitAnalysis {
  overall_fit?: {
    score?: number
    level?: string | null
    recommendation?: string | null
  }
  summary?: string | null
  strong_matches?: string[]
  gaps?: string[]
  ats_keywords_to_add?: string[]
  cv_customization_points?: string[]
  motivation_letter_points?: string[]
  interview_topics_to_prepare?: string[]
  red_flags_or_unclear_points?: string[]
}

export interface ApplicationCvFitStatus {
  status?: 'queued' | 'processing' | 'completed' | 'failed' | null
  requestedAt?: string | null
  completedAt?: string | null
  result?: ApplicationCvFitAnalysis | null
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
  oauthProvider?: 'google' | 'microsoft' | null
  tokenExpiresAt?: string
  createdAt: string
  updatedAt?: string
}

export interface AppConfig {
  simulationMode: boolean
}

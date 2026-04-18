import type { Application, ApplicationFormValues } from '@/types/models.types'

export interface JobOfferWritePayload {
  title?: string
  company?: string
  location?: string
  url?: string
  notes?: string
  recruiterContactEmail?: string
  sourceUrl?: string
  interviewPrep?: string
}

export interface ApplicationWritePayload {
  jobOffer?: string
  status?: Application['status']
  pipelinePosition?: number
  appliedAt?: string | null
}

export function applicationDto(apiApplication: any): Application {
  return {
    id: apiApplication.id,
    jobTitle: apiApplication.jobOffer?.title || '',
    companyName: apiApplication.jobOffer?.company || '',
    location: apiApplication.jobOffer?.location || '',
    url: apiApplication.jobOffer?.url,
    notes: apiApplication.jobOffer?.notes,
    interviewPrep: apiApplication.jobOffer?.interviewPrep,
    rawContent: apiApplication.jobOffer?.rawContent,
    jobSummary: apiApplication.jobOffer?.jobSummary,
    salaryMin: apiApplication.jobOffer?.salaryMin,
    salaryMax: apiApplication.jobOffer?.salaryMax,
    salaryCurrency: apiApplication.jobOffer?.salaryCurrency,
    contractType: apiApplication.jobOffer?.contractType,
    remotePolicy: apiApplication.jobOffer?.remotePolicy,
    details: apiApplication.jobOffer?.details,
    status: apiApplication.status,
    pipelinePosition: apiApplication.pipelinePosition,
    appliedAt: apiApplication.appliedAt,
    lastActivityAt: apiApplication.lastActivityAt,
    createdAt: apiApplication.createdAt,
    updatedAt: apiApplication.updatedAt,
    history: apiApplication.history,
    recruiterEmails: apiApplication.recruiterEmails,
    interviews: apiApplication.interviews,
    scheduledFollowUps: apiApplication.scheduledFollowUps,
    cvFitAnalysisStatus: apiApplication.cvFitAnalysisStatus,
    cvFitAnalysisResult: apiApplication.cvFitAnalysisResult,
    cvFitAnalysisRequestedAt: apiApplication.cvFitAnalysisRequestedAt,
    cvFitAnalysisCompletedAt: apiApplication.cvFitAnalysisCompletedAt,
    jobOffer: {
      ...apiApplication.jobOffer,
      recruiterContactEmail: apiApplication.jobOffer?.recruiterContactEmail || '',
    },
  }
}

export function jobOfferPatchDTO(application: Partial<ApplicationFormValues>): JobOfferWritePayload {
  return {
    title: application.jobTitle,
    company: application.companyName,
    location: application.location || undefined,
    url: application.jobUrl || '',
    notes: application.notes || undefined,
    recruiterContactEmail: application.recruiterContactEmail || undefined,
    sourceUrl: application.sourceUrl || undefined,
    interviewPrep: application.interviewPrep || undefined,
  }
}

export function applicationPostDto(
  jobOfferIri: string,
  application: Partial<ApplicationFormValues>,
): ApplicationWritePayload {
  return {
    jobOffer: jobOfferIri,
    status: application.status,
    pipelinePosition: application.pipelinePosition ?? 0,
    appliedAt: application.appliedAt || null,
  }
}

export function applicationPatchDto(application: Partial<ApplicationFormValues>): ApplicationWritePayload {
  return {
    status: application.status,
    pipelinePosition: application.pipelinePosition,
    appliedAt: application.appliedAt || null,
  }
}

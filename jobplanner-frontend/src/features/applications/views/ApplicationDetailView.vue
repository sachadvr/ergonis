<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { format } from 'date-fns'
import {
  ArrowLeft,
  Building2,
  CalendarDays,
  ExternalLink,
  Mail,
  MapPin,
  GripVertical,
  ScrollText,
  Sparkles,
  Target,
} from 'lucide-vue-next'
import { useApplicationsStore } from '../stores/applications.store'
import { useSettingsStore } from '@/features/settings/stores/settings.store'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import RichTextEditor from '@/components/ui/rich-text-editor/RichTextEditor.vue'
import { emailsApi } from '@/features/emails/api/emails.api'
import { interviewsApi } from '@/features/interviews/api/interviews.api'
import { extractIdFromIri } from '@/lib/api/transforms'
import type { Application, ApplicationFormValues, Interview } from '@/types/models.types'

const route = useRoute()
const router = useRouter()
const applicationsStore = useApplicationsStore()
const settingsStore = useSettingsStore()
const { currentApplication, isLoading } = storeToRefs(applicationsStore)
const { mailboxSettings } = storeToRefs(settingsStore)

type BlockId =
  | 'overview'
  | 'recruiterEmails'
  | 'notes'
  | 'interviewPrep'
  | 'metadata'
  | 'interviews'
  | 'followups'
  | 'history'

const detailedInterviews = ref<Interview[]>([])
const editableForm = ref<ApplicationFormValues | null>(null)
const inlineSaveState = ref<'idle' | 'saving' | 'saved' | 'error'>('idle')
const recruiterEmailSaveState = ref<'idle' | 'saving' | 'saved' | 'error'>('idle')
const saveErrorMessage = ref('')
const recruiterEmailError = ref('')
const deleteState = ref<'idle' | 'deleting' | 'error'>('idle')
const deleteErrorMessage = ref('')
const jobTitleEditorRef = ref<HTMLElement | null>(null)
const companyEditorRef = ref<HTMLElement | null>(null)
const companyOverviewEditorRef = ref<HTMLElement | null>(null)
const locationEditorRef = ref<HTMLElement | null>(null)
const jobUrlEditorRef = ref<HTMLElement | null>(null)
const recruiterContactEmailEditorRef = ref<HTMLElement | null>(null)
const recruiterSenderRef = ref<HTMLElement | null>(null)
const recruiterSubjectRef = ref<HTMLElement | null>(null)
const recruiterBodyRef = ref<HTMLElement | null>(null)
const blockOrder = ref<BlockId[]>([
  'overview',
  'recruiterEmails',
  'notes',
  'interviewPrep',
  'metadata',
  'interviews',
  'followups',
  'history',
])
const draggedBlock = ref<BlockId | null>(null)
const dragOverTarget = ref<BlockId | null>(null)

const notesDraftHtml = ref('')
const interviewPrepDraftHtml = ref('')
let recruiterSenderDraft = ''
let recruiterSubjectDraft = ''
let recruiterBodyDraft = ''
let saveDebounceTimer: ReturnType<typeof setTimeout> | null = null
let saveStateTimer: ReturnType<typeof setTimeout> | null = null
let recruiterEmailStateTimer: ReturnType<typeof setTimeout> | null = null

const applicationId = computed(() => String(route.params.id))
const currentMailboxImapUser = computed(() => mailboxSettings.value[0]?.imapUser || '')

const statusVariant = (status: Application['status']) => {
  if (status === 'rejected') return 'destructive'
  if (status === 'accepted' || status === 'offer') return 'success'
  if (status === 'interview') return 'warning'
  if (status === 'wishlist') return 'secondary'
  return 'info'
}

const statusLabel = (status: Application['status']) => status.replace('_', ' ')

const formatDateTime = (value?: string) => {
  if (!value) return 'Not available'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Not available'
  return format(date, 'PP p')
}

const formatDate = (value?: string) => {
  if (!value) return 'Not available'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Not available'
  return format(date, 'PPP')
}

const loadApplication = async () => {
  await applicationsStore.fetchApplicationById(applicationId.value)

  const interviews = currentApplication.value?.interviews || []
  const iriInterviews = interviews.filter((interview): interview is string => typeof interview === 'string')

  if (!iriInterviews.length) {
    detailedInterviews.value = interviews.filter((interview): interview is Interview => typeof interview === 'object')
    return
  }

  const resolved = await Promise.all(
    iriInterviews
      .map((iri) => extractIdFromIri(iri))
      .filter((id): id is string => Boolean(id))
      .map((id) => interviewsApi.getById(id)),
  )

  detailedInterviews.value = resolved
}

const interviewsToDisplay = computed(() => {
  const interviews = currentApplication.value?.interviews || []
  const objectInterviews = interviews.filter((interview): interview is Interview => typeof interview === 'object')
  return objectInterviews.length ? objectInterviews : detailedInterviews.value
})
const currentJobOfferId = computed(() => currentApplication.value?.jobOffer?.id)

const formatSalary = (min?: number, max?: number, currency?: string) => {
  if (min == null && max == null) return null

  const formatValue = (value?: number) => (value == null ? null : value.toLocaleString())
  const minLabel = formatValue(min)
  const maxLabel = formatValue(max)
  const suffix = currency ? ` ${currency}` : ''

  if (minLabel && maxLabel) return `${minLabel} - ${maxLabel}${suffix}`
  if (minLabel) return `From ${minLabel}${suffix}`
  if (maxLabel) return `Up to ${maxLabel}${suffix}`

  return null
}

const getDetailSummary = () => {
  const details = currentApplication.value?.jobOffer?.details
  if (!details || typeof details !== 'object') return []

  const entries: Array<{ label: string; value: string }> = []
  const data = details as Record<string, unknown>
  const location = data.location as Record<string, unknown> | undefined
  const candidateFit = data.candidate_fit as Record<string, unknown> | undefined

  const push = (label: string, value: unknown) => {
    if (value === null || value === undefined) return
    const text = String(value).trim()
    if (!text) return
    entries.push({ label, value: text })
  }

  push('City', location?.city)
  push('Country', location?.country)
  push('Remote policy', location?.remote_policy)
  push('Seniority', candidateFit?.seniority_level)
  push('Target profile', candidateFit?.target_profile)

  return entries
}

const normalizeStringList = (value: unknown): string[] => {
  if (!Array.isArray(value)) return []

  return value
    .map((item) => (typeof item === 'string' ? item.trim() : String(item ?? '').trim()))
    .filter((item): item is string => item.length > 0)
}

const getResponsibilities = () => {
  const details = currentApplication.value?.jobOffer?.details
  if (!details || typeof details !== 'object') return []

  const data = details as Record<string, unknown>
  return normalizeStringList(data.main_responsibilities)
}

const getRequiredSkillGroups = () => {
  const details = currentApplication.value?.jobOffer?.details
  if (!details || typeof details !== 'object') return []

  const required = (details as Record<string, unknown>).required_skills as Record<string, unknown> | undefined
  if (!required) return []

  return [
    { label: 'Technical', items: normalizeStringList(required.technical) },
    { label: 'Tools', items: normalizeStringList(required.tools) },
    { label: 'Soft skills', items: normalizeStringList(required.soft_skills) },
    { label: 'Languages', items: normalizeStringList(required.languages) },
    { label: 'Certifications', items: normalizeStringList(required.certifications) },
    { label: 'Education', items: normalizeStringList(required.education) },
    { label: 'Experience', items: normalizeStringList(required.experience) },
  ].filter((group) => group.items.length > 0)
}

const toEditableForm = (application: Application): ApplicationFormValues => ({
  jobTitle: application.jobTitle,
  companyName: application.companyName,
  location: application.location || '',
  status: application.status,
  jobUrl: application.url || '',
  salaryMin: application.salaryMin,
  salaryMax: application.salaryMax,
  notes: application.notes || '',
  appliedAt: application.appliedAt,
  pipelinePosition: application.pipelinePosition,
  recruiterContactEmail: application.jobOffer?.recruiterContactEmail || '',
  sourceUrl: application.jobOffer?.sourceUrl || '',
  interviewPrep: application.interviewPrep || '',
})

const resetRecruiterEmailDraft = () => {
  recruiterSenderDraft = currentMailboxImapUser.value || currentApplication.value?.jobOffer?.recruiterContactEmail || ''
  recruiterSubjectDraft = `Re: ${currentApplication.value?.jobTitle || ''}`.trim()
  recruiterBodyDraft = ''

  if (recruiterSenderRef.value) recruiterSenderRef.value.innerText = recruiterSenderDraft
  if (recruiterSubjectRef.value) recruiterSubjectRef.value.innerText = recruiterSubjectDraft
  if (recruiterBodyRef.value) recruiterBodyRef.value.innerText = recruiterBodyDraft
}

const hasInlineChanges = computed(() => {
  if (!currentApplication.value || !editableForm.value) return false
  const live = toEditableForm(currentApplication.value)
  const draft = {
    ...editableForm.value,
    notes: notesDraftHtml.value,
    interviewPrep: interviewPrepDraftHtml.value,
  }
  return JSON.stringify(live) !== JSON.stringify(draft)
})

const canManualSave = computed(() => inlineSaveState.value !== 'saving')

const normalizeInlineText = (value: string) => value.replace(/\n+/g, ' ').trim()

const syncPlainEditorText = (editor: HTMLElement | null, value: string) => {
  if (!editor) return
  if (editor.innerText !== value) {
    editor.innerText = value
  }
}

const syncPlainField = (field: keyof ApplicationFormValues, editor: HTMLElement | null) => {
  if (!editableForm.value || !editor) return
  editableForm.value[field] = normalizeInlineText(editor.innerText) as never
}

const syncPlainEditableDrafts = () => {
  syncPlainField('jobTitle', jobTitleEditorRef.value)
  syncPlainField('companyName', companyEditorRef.value)
  syncPlainField('companyName', companyOverviewEditorRef.value)
  syncPlainField('location', locationEditorRef.value)
  syncPlainField('jobUrl', jobUrlEditorRef.value)
}

const syncRecruiterEmailDrafts = () => {
  recruiterSenderDraft = normalizeInlineText(recruiterSenderRef.value?.innerText || '')
  recruiterSubjectDraft = normalizeInlineText(recruiterSubjectRef.value?.innerText || '')
  recruiterBodyDraft = (recruiterBodyRef.value?.innerText || '').trim()
}

const queueSave = () => {
  if (saveDebounceTimer) {
    clearTimeout(saveDebounceTimer)
  }

  saveDebounceTimer = setTimeout(() => {
    saveInlineChanges()
  }, 700)
}

const resolveJobOfferId = () => {
  const jobOffer = currentApplication.value?.jobOffer as { id?: number; '@id'?: string } | undefined
  if (typeof jobOffer?.id === 'number') return jobOffer.id

  const match = jobOffer?.['@id']?.match(/\/(\d+)$/)
  return match ? Number(match[1]) : undefined
}

const saveInlineChanges = async () => {
  syncPlainEditableDrafts()

  if (!currentApplication.value || !editableForm.value || !hasInlineChanges.value) return

  if (saveDebounceTimer) {
    clearTimeout(saveDebounceTimer)
    saveDebounceTimer = null
  }

  if (saveStateTimer) {
    clearTimeout(saveStateTimer)
    saveStateTimer = null
  }

  inlineSaveState.value = 'saving'
  saveErrorMessage.value = ''

  try {
    await applicationsStore.updateApplication(
      currentApplication.value.id,
      {
        ...editableForm.value,
        notes: notesDraftHtml.value,
        interviewPrep: interviewPrepDraftHtml.value,
      },
      resolveJobOfferId(),
    )
    inlineSaveState.value = 'saved'
    saveStateTimer = setTimeout(() => {
      inlineSaveState.value = 'idle'
    }, 1400)
  } catch {
    inlineSaveState.value = 'error'
    saveErrorMessage.value = 'Could not save changes. Please retry.'
  }
}

const handlePlainEditableInput = (field: keyof ApplicationFormValues, event: Event) => {
  if (!editableForm.value) return

  const content = normalizeInlineText((event.target as HTMLElement).innerText)
  editableForm.value[field] = content as never
  queueSave()
}

const commitInlineTextField = (field: keyof ApplicationFormValues, event: Event) => {
  handlePlainEditableInput(field, event)
  flushInlineSave()
}

const flushInlineSave = () => {
  saveInlineChanges()
}

const createRecruiterEmail = async () => {
  if (!currentApplication.value) return

  syncRecruiterEmailDrafts()
  const sender = recruiterSenderDraft.trim()
  const subject = recruiterSubjectDraft.trim()
  const body = recruiterBodyDraft.trim()

  if (!sender || !subject || !body) {
    recruiterEmailError.value = 'Sender, subject and body are required.'
    recruiterEmailSaveState.value = 'error'
    return
  }

  recruiterEmailSaveState.value = 'saving'
  recruiterEmailError.value = ''

  try {
    await emailsApi.create({
      application: `/api/applications/${currentApplication.value.id}`,
      sender,
      subject,
      body,
      receivedAt: new Date().toISOString(),
      direction: 'INCOMING',
      isFavourite: false,
      isDeleted: false,
      isDraft: false,
      labels: [],
    })

    recruiterEmailSaveState.value = 'saved'
    await applicationsStore.fetchApplicationById(applicationId.value)
    resetRecruiterEmailDraft()
    if (recruiterEmailStateTimer) clearTimeout(recruiterEmailStateTimer)
    recruiterEmailStateTimer = setTimeout(() => {
      recruiterEmailSaveState.value = 'idle'
    }, 1400)
  } catch {
    recruiterEmailSaveState.value = 'error'
    recruiterEmailError.value = 'Could not add recruiter email.'
  }
}

const deleteApplicationAndJobOffer = async () => {
  if (!currentApplication.value) return

  const confirmed = window.confirm('Delete this application and its job offer? This cannot be undone.')
  if (!confirmed) return

  deleteState.value = 'deleting'
  deleteErrorMessage.value = ''

  try {
    await applicationsStore.deleteApplicationWithJobOffer(currentApplication.value.id, currentJobOfferId.value)
    await router.push({ name: 'Applications' })
  } catch {
    deleteState.value = 'error'
    deleteErrorMessage.value = 'Could not delete the application.'
  }
}

const getBlockOrderStyle = (blockId: BlockId) => ({
  order: blockOrder.value.indexOf(blockId) + 1,
})

const handleDragStart = (blockId: BlockId, event: DragEvent) => {
  draggedBlock.value = blockId
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
  }
}

const handleDragEnd = () => {
  draggedBlock.value = null
  dragOverTarget.value = null
}

const handleDragOver = (targetBlock: BlockId) => {
  if (!draggedBlock.value || draggedBlock.value === targetBlock) return
  dragOverTarget.value = targetBlock
}

const handleDragLeave = (targetBlock: BlockId) => {
  if (dragOverTarget.value === targetBlock) {
    dragOverTarget.value = null
  }
}

const handleDrop = (targetBlock: BlockId) => {
  if (!draggedBlock.value || draggedBlock.value === targetBlock) return

  const fromIndex = blockOrder.value.indexOf(draggedBlock.value)
  const toIndex = blockOrder.value.indexOf(targetBlock)

  if (fromIndex !== -1 && toIndex !== -1) {
    const [movedBlock] = blockOrder.value.splice(fromIndex, 1)
    if (movedBlock) {
      blockOrder.value.splice(toIndex, 0, movedBlock)
    }
  }

  draggedBlock.value = null
  dragOverTarget.value = null
}

const handleSaveShortcut = (event: KeyboardEvent) => {
  const isSaveShortcut = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's'
  if (!isSaveShortcut) return
  event.preventDefault()
  saveInlineChanges()
}

watch(currentApplication, (application) => {
  if (!application || editableForm.value) return

  editableForm.value = toEditableForm(application)

  notesDraftHtml.value = application.notes || ''
  interviewPrepDraftHtml.value = application.interviewPrep || ''

  nextTick(() => {
    syncPlainEditorText(jobTitleEditorRef.value, editableForm.value?.jobTitle || '')
    syncPlainEditorText(companyEditorRef.value, editableForm.value?.companyName || '')
    syncPlainEditorText(companyOverviewEditorRef.value, editableForm.value?.companyName || '')
    syncPlainEditorText(locationEditorRef.value, editableForm.value?.location || '')
    syncPlainEditorText(jobUrlEditorRef.value, editableForm.value?.jobUrl || '')
    syncPlainEditorText(recruiterContactEmailEditorRef.value, editableForm.value?.recruiterContactEmail || '')
    syncPlainEditorText(recruiterSenderRef.value, recruiterSenderDraft)
    syncPlainEditorText(recruiterSubjectRef.value, recruiterSubjectDraft)
    syncPlainEditorText(recruiterBodyRef.value, recruiterBodyDraft)
  })

  resetRecruiterEmailDraft()
})

watch(applicationId, () => {
  editableForm.value = null
  notesDraftHtml.value = ''
  interviewPrepDraftHtml.value = ''
  loadApplication()
}, { immediate: true })

watch(currentMailboxImapUser, (imapUser) => {
  if (!imapUser) return
  recruiterSenderDraft = imapUser
  if (recruiterSenderRef.value) recruiterSenderRef.value.innerText = imapUser
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleSaveShortcut)
  if (saveDebounceTimer) clearTimeout(saveDebounceTimer)
  if (saveStateTimer) clearTimeout(saveStateTimer)
  if (recruiterEmailStateTimer) clearTimeout(recruiterEmailStateTimer)
})

onMounted(() => {
  if (!mailboxSettings.value.length) {
    settingsStore.fetchSettings()
  }

  window.addEventListener('keydown', handleSaveShortcut)
})
</script>

<template>
  <div class="space-y-6" v-if="currentApplication">
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="icon" @click="router.push({ name: 'Applications' })">
          <ArrowLeft :size="18" />
        </Button>
        <div>
          <p class="section-kicker mb-2">Application detail</p>
            <h1
              ref="jobTitleEditorRef"
              class="display-title editable-inline text-4xl font-semibold"
              contenteditable="true"
              @keydown.enter.prevent
              @input="handlePlainEditableInput('jobTitle', $event)"
              @blur="commitInlineTextField('jobTitle', $event)"
            ></h1>
            <p
              ref="companyEditorRef"
              class="editable-inline mt-2 text-muted-foreground"
              contenteditable="true"
              @keydown.enter.prevent
              @input="handlePlainEditableInput('companyName', $event)"
              @blur="commitInlineTextField('companyName', $event)"
            ></p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <Badge :variant="statusVariant(currentApplication.status)">{{ statusLabel(currentApplication.status) }}</Badge>
        <Button
          variant="outline"
          size="sm"
          :disabled="!canManualSave"
          @click="saveInlineChanges"
        >
          Save
        </Button>
        <Button
          variant="destructive"
          size="sm"
          class="!bg-red-600 !text-white hover:!bg-red-700"
          :disabled="deleteState === 'deleting'"
          @click="deleteApplicationAndJobOffer"
        >
          Delete
        </Button>
        <div v-if="inlineSaveState === 'saving'" class="text-xs text-muted-foreground">Saving...</div>
        <div v-else-if="inlineSaveState === 'saved'" class="text-xs text-emerald-700">Saved</div>
        <div v-else-if="inlineSaveState === 'error'" class="text-xs text-destructive">{{ saveErrorMessage }}</div>
        <div v-if="deleteState === 'deleting'" class="text-xs text-muted-foreground">Deleting...</div>
        <div v-else-if="deleteState === 'error'" class="text-xs text-destructive">{{ deleteErrorMessage }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <Card
        :class="['cursor-default', dragOverTarget === 'overview' ? 'drag-over-card' : '']"
        :style="getBlockOrderStyle('overview')"
        @dragenter.prevent="handleDragOver('overview')"
        @dragover.prevent="handleDragOver('overview')"
        @dragleave="handleDragLeave('overview')"
        @drop="handleDrop('overview')"
      >
        <CardHeader class="flex flex-row items-center justify-between gap-4">
          <CardTitle class="flex items-center gap-2 bg-[#161632] text-white p-2 rounded-sm -mb-2"><Building2 :size="18" /> Overview</CardTitle>
          <button type="button" class="drag-handle" draggable="true" title="Drag to reorder" @dragstart="handleDragStart('overview', $event)" @dragend="handleDragEnd">
            <GripVertical :size="16" />
          </button>
        </CardHeader>
        <CardContent class="grid gap-4 md:grid-cols-2">
          <div class="rounded-2xl bg-secondary/50 p-4">
            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Company</div>
            <div
              ref="companyOverviewEditorRef"
              class="editable-inline mt-2 px-3 py-2 text-lg font-semibold"
              data-placeholder="Add company name"
              contenteditable="true"
              @keydown.enter.prevent
              @input="handlePlainEditableInput('companyName', $event)"
              @blur="commitInlineTextField('companyName', $event)"
            ></div>
          </div>
          <div class="rounded-2xl bg-secondary/50 p-4">
            <div class="flex items-center gap-2 text-xs uppercase tracking-[0.16em] text-muted-foreground"><MapPin :size="14" /> Location</div>
            <div ref="locationEditorRef" class="editable-inline mt-2 text-base font-medium" data-placeholder="Add a location" contenteditable="true" @keydown.enter.prevent @input="handlePlainEditableInput('location', $event)" @blur="commitInlineTextField('location', $event)"></div>
          </div>
          <div v-if="currentApplication.appliedAt" class="rounded-2xl bg-secondary/50 p-4">
            <div class="flex items-center gap-2 text-xs uppercase tracking-[0.16em] text-muted-foreground"><CalendarDays :size="14" /> Applied at</div>
            <div class="mt-2 text-base font-medium">{{ formatDate(currentApplication.appliedAt) }}</div>
          </div>
          <div class="rounded-2xl bg-secondary/50 p-4 md:col-span-2">
            <div class="flex items-center gap-2 text-xs uppercase tracking-[0.16em] text-muted-foreground"><ExternalLink :size="14" /> Job URL</div>
            <div ref="jobUrlEditorRef" class="editable-inline mt-2 text-base font-medium" data-placeholder="Paste job posting URL" contenteditable="true" @keydown.enter.prevent @input="handlePlainEditableInput('jobUrl', $event)" @blur="commitInlineTextField('jobUrl', $event)"></div>
            <a v-if="editableForm?.jobUrl" :href="editableForm.jobUrl" target="_blank" rel="noreferrer" class="mt-2 inline-flex items-center gap-2 text-base font-medium text-primary hover:underline">
              Open posting
              <ExternalLink :size="14" />
            </a>
          </div>
          <div v-if="currentApplication.jobOffer?.jobSummary" class="rounded-2xl bg-secondary/50 p-4 md:col-span-2">
            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Job summary</div>
            <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-foreground">{{ currentApplication.jobOffer.jobSummary }}</p>
          </div>
          <div v-if="formatSalary(currentApplication.jobOffer?.salaryMin, currentApplication.jobOffer?.salaryMax, currentApplication.jobOffer?.salaryCurrency)" class="rounded-2xl bg-secondary/50 p-4">
            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Salary</div>
            <div class="mt-2 text-base font-medium">{{ formatSalary(currentApplication.jobOffer?.salaryMin, currentApplication.jobOffer?.salaryMax, currentApplication.jobOffer?.salaryCurrency) }}</div>
          </div>
          <div v-if="currentApplication.jobOffer?.contractType" class="rounded-2xl bg-secondary/50 p-4">
            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Contract</div>
            <div class="mt-2 text-base font-medium">{{ currentApplication.jobOffer.contractType }}</div>
          </div>
          <div v-if="currentApplication.jobOffer?.remotePolicy" class="rounded-2xl bg-secondary/50 p-4 md:col-span-2">
            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Remote policy</div>
            <div class="mt-2 text-base font-medium">{{ currentApplication.jobOffer.remotePolicy }}</div>
          </div>
          <div v-if="currentApplication.jobOffer?.details" class="rounded-2xl bg-secondary/50 p-4 md:col-span-2">
            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Useful details</div>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
              <div v-for="item in getDetailSummary()" :key="item.label" class="rounded-xl bg-card/80 p-3">
                <div class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">{{ item.label }}</div>
                <div class="mt-1 text-sm font-medium text-foreground">{{ item.value }}</div>
              </div>
            </div>
            <div v-if="getResponsibilities().length" class="mt-5 rounded-xl bg-card/80 p-3">
              <div class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">Responsibilities</div>
              <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6 text-foreground">
                <li v-for="item in getResponsibilities()" :key="item">{{ item }}</li>
              </ul>
            </div>
            <div v-if="getRequiredSkillGroups().length" class="mt-5 space-y-3">
              <div class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">Required skills</div>
              <div v-for="group in getRequiredSkillGroups()" :key="group.label" class="rounded-xl bg-card/80 p-3">
                <div class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">{{ group.label }}</div>
                <div class="mt-2 flex flex-wrap gap-2">
                  <span
                    v-for="skill in group.items"
                    :key="skill"
                    class="rounded-full border border-border/70 bg-background px-3 py-1 text-xs font-medium text-foreground"
                  >
                    {{ skill }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card
        :class="['cursor-default', dragOverTarget === 'recruiterEmails' ? 'drag-over-card' : '']"
        :style="getBlockOrderStyle('recruiterEmails')"
        @dragenter.prevent="handleDragOver('recruiterEmails')"
        @dragover.prevent="handleDragOver('recruiterEmails')"
        @dragleave="handleDragLeave('recruiterEmails')"
        @drop="handleDrop('recruiterEmails')"
      >
        <CardHeader class="flex flex-row items-center justify-between gap-4">
          <CardTitle>Recruiter Emails</CardTitle>
          <button type="button" class="drag-handle" draggable="true" title="Drag to reorder" @dragstart="handleDragStart('recruiterEmails', $event)" @dragend="handleDragEnd">
            <GripVertical :size="16" />
          </button>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2 rounded-[1.25rem] border border-border/80 bg-secondary/30 p-4">
            <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Contact email</div>
            <div
              ref="recruiterContactEmailEditorRef"
              class="editable-inline mt-2 px-3 py-2 text-sm font-medium"
              data-placeholder="Add recruiter email"
              contenteditable="true"
              @keydown.enter.prevent
              @input="handlePlainEditableInput('recruiterContactEmail', $event)"
              @blur="commitInlineTextField('recruiterContactEmail', $event)"
            ></div>
          </div>
          <div v-for="email in currentApplication.recruiterEmails" :key="email.id" class="rounded-[1.25rem] border border-border/80 bg-card/70 p-4">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="font-medium text-foreground">{{ email.subject }}</div>
                <div class="mt-1 flex items-center gap-2 text-sm text-muted-foreground"><Mail :size="14" /> {{ email.sender }}</div>
              </div>
              <div class="text-xs text-muted-foreground">{{ format(new Date(email.receivedAt), 'PP p') }}</div>
            </div>
            <div class="mt-3 whitespace-pre-wrap text-sm leading-6 text-foreground">{{ email.body }}</div>
            <div v-if="email.aiSummary" class="mt-3 rounded-xl bg-accent/40 p-3 text-sm text-muted-foreground border">
              <div class="mb-1 flex items-center gap-2 font-medium text-foreground"><Sparkles :size="14" /> AI Summary</div>
              {{ email.aiSummary }}
            </div>
          </div>

          <div class="rounded-[1.25rem] border border-dashed border-border/80 bg-secondary/30 p-4 space-y-3">
            <div>
              <div class="grid grid-cols-2 justify-center items-center">
                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground mb-1">Sender</div>
                <div ref="recruiterSenderRef" class="editable-inline min-h-10 px-3 py-2 text-sm" contenteditable="true" @keydown.enter.prevent @blur="syncRecruiterEmailDrafts"></div>
              </div>
              <div class="grid grid-cols-2 justify-center items-center">
                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground mb-1">Subject</div>
                <div ref="recruiterSubjectRef" class="editable-inline min-h-10 px-3 py-2 text-sm" contenteditable="true" @keydown.enter.prevent @blur="syncRecruiterEmailDrafts"></div>
              </div>
            </div>
            <div>
              <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground mb-1">Body</div>
              <div ref="recruiterBodyRef" class="editable-inline min-h-24 px-3 py-2 text-sm whitespace-pre-wrap" contenteditable="true" @blur="syncRecruiterEmailDrafts"></div>
            </div>
            <div class="flex items-center gap-3">
              <Button size="sm" :disabled="recruiterEmailSaveState === 'saving'" @click="createRecruiterEmail">Add email</Button>
              <div v-if="recruiterEmailSaveState === 'saving'" class="text-xs text-muted-foreground">Saving...</div>
              <div v-else-if="recruiterEmailSaveState === 'saved'" class="text-xs text-emerald-700">Saved</div>
              <div v-else-if="recruiterEmailSaveState === 'error'" class="text-xs text-destructive">{{ recruiterEmailError }}</div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card
        :class="['cursor-default', dragOverTarget === 'notes' ? 'drag-over-card' : '']"
        :style="getBlockOrderStyle('notes')"
        @dragenter.prevent="handleDragOver('notes')"
        @dragover.prevent="handleDragOver('notes')"
        @dragleave="handleDragLeave('notes')"
        @drop="handleDrop('notes')"
      >
        <CardHeader class="flex flex-row items-center justify-between gap-4">
          <CardTitle class="flex items-center gap-2"><ScrollText :size="18" /> Notes</CardTitle>
          <button type="button" class="drag-handle" draggable="true" title="Drag to reorder" @dragstart="handleDragStart('notes', $event)" @dragend="handleDragEnd">
            <GripVertical :size="16" />
          </button>
        </CardHeader>
        <CardContent>
          <RichTextEditor
            v-model="notesDraftHtml"
            placeholder="Write notes, format text, and highlight key points..."
            @blur="flushInlineSave"
          />
        </CardContent>
      </Card>

      <Card
        :class="['cursor-default', dragOverTarget === 'interviewPrep' ? 'drag-over-card' : '']"
        :style="getBlockOrderStyle('interviewPrep')"
        @dragenter.prevent="handleDragOver('interviewPrep')"
        @dragover.prevent="handleDragOver('interviewPrep')"
        @dragleave="handleDragLeave('interviewPrep')"
        @drop="handleDrop('interviewPrep')"
      >
        <CardHeader class="flex flex-row items-center justify-between gap-4">
          <CardTitle class="flex items-center gap-2"><Target :size="18" /> Interview prep</CardTitle>
          <button type="button" class="drag-handle" draggable="true" title="Drag to reorder" @dragstart="handleDragStart('interviewPrep', $event)" @dragend="handleDragEnd">
            <GripVertical :size="16" />
          </button>
        </CardHeader>
        <CardContent>
          <RichTextEditor
            v-model="interviewPrepDraftHtml"
            placeholder="Capture prep notes, likely questions, and talking points..."
            @blur="flushInlineSave"
          />
        </CardContent>
      </Card>

      <Card
        :class="['cursor-default', dragOverTarget === 'metadata' ? 'drag-over-card' : '']"
        :style="getBlockOrderStyle('metadata')"
        @dragenter.prevent="handleDragOver('metadata')"
        @dragover.prevent="handleDragOver('metadata')"
        @dragleave="handleDragLeave('metadata')"
        @drop="handleDrop('metadata')"
      >
        <CardHeader class="flex flex-row items-center justify-between gap-4">
          <CardTitle>Application Metadata</CardTitle>
          <button type="button" class="drag-handle" draggable="true" title="Drag to reorder" @dragstart="handleDragStart('metadata', $event)" @dragend="handleDragEnd">
            <GripVertical :size="16" />
          </button>
        </CardHeader>
        <CardContent class="space-y-4 text-sm">
          <div class="flex items-center justify-between"><span class="text-muted-foreground">Created</span><strong>{{ formatDateTime(currentApplication.createdAt) }}</strong></div>
          <div class="flex items-center justify-between"><span class="text-muted-foreground">Updated</span><strong>{{ formatDateTime(currentApplication.updatedAt) }}</strong></div>
          <div v-if="currentApplication.lastActivityAt" class="flex items-center justify-between"><span class="text-muted-foreground">Last activity</span><strong>{{ formatDateTime(currentApplication.lastActivityAt) }}</strong></div>
          <div v-if="currentApplication.jobOffer?.sourceUrl" class="space-y-2">
            <div class="text-muted-foreground">Source URL</div>
            <a :href="currentApplication.jobOffer.sourceUrl" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 text-primary hover:underline">
              {{ currentApplication.jobOffer.sourceUrl }}
            </a>
          </div>
        </CardContent>
      </Card>

      <Card
        :class="['cursor-default', dragOverTarget === 'interviews' ? 'drag-over-card' : '']"
        :style="getBlockOrderStyle('interviews')"
        @dragenter.prevent="handleDragOver('interviews')"
        @dragover.prevent="handleDragOver('interviews')"
        @dragleave="handleDragLeave('interviews')"
        @drop="handleDrop('interviews')"
      >
        <CardHeader class="flex flex-row items-center justify-between gap-4">
          <CardTitle>Interviews</CardTitle>
          <button type="button" class="drag-handle" draggable="true" title="Drag to reorder" @dragstart="handleDragStart('interviews', $event)" @dragend="handleDragEnd">
            <GripVertical :size="16" />
          </button>
        </CardHeader>
        <CardContent class="space-y-3">
          <div v-if="!interviewsToDisplay.length" class="text-sm text-muted-foreground">No interviews recorded.</div>
          <div v-for="interview in interviewsToDisplay" :key="interview.id" class="rounded-2xl bg-secondary/50 p-4 text-sm">
            <div class="font-medium">{{ format(new Date(interview.scheduledAt), 'PPP p') }}</div>
            <div class="mt-1 text-muted-foreground">{{ interview.type }}</div>
            <div v-if="interview.locationOrLink" class="mt-2 text-muted-foreground">{{ interview.locationOrLink }}</div>
            <div v-if="interview.notes" class="mt-2 whitespace-pre-wrap text-foreground">{{ interview.notes }}</div>
          </div>
        </CardContent>
      </Card>

      <Card
        :class="['cursor-default', dragOverTarget === 'followups' ? 'drag-over-card' : '']"
        :style="getBlockOrderStyle('followups')"
        @dragenter.prevent="handleDragOver('followups')"
        @dragover.prevent="handleDragOver('followups')"
        @dragleave="handleDragLeave('followups')"
        @drop="handleDrop('followups')"
      >
        <CardHeader class="flex flex-row items-center justify-between gap-4">
          <CardTitle>Scheduled Follow-ups</CardTitle>
          <button type="button" class="drag-handle" draggable="true" title="Drag to reorder" @dragstart="handleDragStart('followups', $event)" @dragend="handleDragEnd">
            <GripVertical :size="16" />
          </button>
        </CardHeader>
        <CardContent class="space-y-3">
          <div v-if="!currentApplication.scheduledFollowUps?.length" class="text-sm text-muted-foreground">No scheduled follow-ups.</div>
          <div v-for="followUp in currentApplication.scheduledFollowUps" :key="followUp.id" class="rounded-2xl bg-secondary/50 p-4 text-sm">
            <div class="font-medium">{{ format(new Date(followUp.scheduledAt), 'PPP p') }}</div>
            <div class="mt-1 text-muted-foreground">{{ followUp.status }}</div>
            <div v-if="followUp.generatedContent" class="mt-2 whitespace-pre-wrap text-foreground">{{ followUp.generatedContent }}</div>
          </div>
        </CardContent>
      </Card>

      <Card
        :class="['cursor-default', dragOverTarget === 'history' ? 'drag-over-card' : '']"
        :style="getBlockOrderStyle('history')"
        @dragenter.prevent="handleDragOver('history')"
        @dragover.prevent="handleDragOver('history')"
        @dragleave="handleDragLeave('history')"
        @drop="handleDrop('history')"
      >
        <CardHeader class="flex flex-row items-center justify-between gap-4">
          <CardTitle>History</CardTitle>
          <button type="button" class="drag-handle" draggable="true" title="Drag to reorder" @dragstart="handleDragStart('history', $event)" @dragend="handleDragEnd">
            <GripVertical :size="16" />
          </button>
        </CardHeader>
        <CardContent class="space-y-3">
          <div v-if="!currentApplication.history?.length" class="text-sm text-muted-foreground">No history yet.</div>
          <div v-for="entry in currentApplication.history" :key="entry.id" class="rounded-2xl bg-secondary/50 p-4 text-sm">
            <div class="font-medium text-foreground">{{ entry.actionType }}</div>
            <div v-if="entry.description" class="mt-1 text-muted-foreground">{{ entry.description }}</div>
            <div class="mt-2 text-xs text-muted-foreground">{{ format(new Date(entry.createdAt), 'PP p') }}</div>
          </div>
        </CardContent>
      </Card>
    </div>

  </div>

  <div v-else class="flex min-h-[320px] items-center justify-center text-muted-foreground">
    {{ isLoading ? 'Loading application...' : 'Application not found.' }}
  </div>
</template>

<style scoped>
.editable-inline {
  border-radius: 0.5rem;
  cursor: text;
  outline: none;
  transition: background-color 0.2s ease;
}

.editable-inline:hover {
  background: color-mix(in srgb, var(--accent) 65%, transparent);
}

.editable-inline:focus {
  background: color-mix(in srgb, var(--accent) 90%, transparent);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary) 28%, transparent);
}

.editable-inline[contenteditable='true']:empty::before {
  color: hsl(var(--muted-foreground));
  content: attr(data-placeholder);
  pointer-events: none;
}

.drag-handle {
  align-items: center;
  border: 1px solid hsl(var(--border));
  border-radius: 0.65rem;
  color: hsl(var(--muted-foreground));
  cursor: grab;
  display: inline-flex;
  justify-content: center;
  padding: 0.2rem;
  user-select: none;
  -webkit-user-select: none;
}

.drag-handle:active {
  cursor: grabbing;
}

.drag-handle:hover {
  background: color-mix(in srgb, var(--accent) 80%, transparent);
}

.drag-over-card {
  border-color: color-mix(in srgb, var(--primary) 45%, hsl(var(--border)));
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary) 22%, transparent);
  background: color-mix(in srgb, var(--accent) 55%, transparent);
}

</style>

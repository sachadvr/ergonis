<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { format } from 'date-fns'
import { Briefcase, ChevronRight, ExternalLink, MailOpen, RefreshCw, Search, Sparkles } from 'lucide-vue-next'
import { useEmailsStore } from '../stores/emails.store'
import { useApplicationsStore } from '@/features/applications/stores/applications.store'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Select, type SelectOption } from '@/components/ui/select'
import type { Application, RecruiterEmail } from '@/types/models.types'

const emailsStore = useEmailsStore()
const applicationsStore = useApplicationsStore()
const { sortedEmails, isLoading, error } = storeToRefs(emailsStore)
const { applications } = storeToRefs(applicationsStore)
const selectedEmailId = ref<number | null>(null)
const search = ref('')
const selectedGroupKey = ref<string | null>(null)
const directionFilter = ref('all')
const aiFilter = ref('all')
const linkedFilter = ref('all')
const companyFilter = ref('all')

const previewBody = (body: string) => body.replace(/\s+/g, ' ').trim().slice(0, 100)

const normalizeSubject = (subject: string) => subject.replace(/^(re|fw|fwd)\s*:\s*/gi, '').trim()

const guessLinkedApplication = (email: RecruiterEmail | null): Application | null => {
  if (!email) return null

  const sender = email.sender.toLowerCase()
  const subject = normalizeSubject(email.subject).toLowerCase()
  const body = `${email.subject} ${email.body}`.toLowerCase()

  const exactRecruiterMatch = applications.value.find((application) => {
    const recruiterEmail = application.jobOffer?.recruiterContactEmail?.toLowerCase()
    return recruiterEmail && recruiterEmail === sender
  })

  if (exactRecruiterMatch) return exactRecruiterMatch

  const fuzzyMatch = applications.value.find((application) => {
    return [application.companyName, application.jobTitle, application.jobOffer?.recruiterContactEmail || '']
      .join(' ')
      .toLowerCase()
      .split(' ')
      .some((token) => token.length > 3 && (subject.includes(token) || body.includes(token)))
  })

  return fuzzyMatch || null
}

const senderDomainFallback = (sender: string) => {
  const domain = sender.split('@')[1] || sender
  return domain.replace(/^www\./i, '')
}

const companyForEmail = (email: RecruiterEmail) => {
  const linked = guessLinkedApplication(email)
  if (linked?.companyName) return linked.companyName
  return senderDomainFallback(email.sender)
}

const getGroupKey = (email: RecruiterEmail) => {
  return companyForEmail(email).toLowerCase()
}

const groupedEmails = computed(() => {
  const groups = new Map<string, {
    key: string
    label: string
    secondary: string
    emails: RecruiterEmail[]
    lastReceivedAt: string
    latestSubject: string
  }>()

  for (const email of sortedEmails.value) {
    const key = getGroupKey(email)
    const company = companyForEmail(email)
    const existing = groups.get(key)

    if (existing) {
      existing.emails.push(email)
      if (new Date(email.receivedAt).getTime() > new Date(existing.lastReceivedAt).getTime()) {
        existing.lastReceivedAt = email.receivedAt
        existing.latestSubject = normalizeSubject(email.subject) || email.subject
      }
    } else {
      groups.set(key, {
        key,
        label: company,
        secondary: email.sender,
        emails: [email],
        lastReceivedAt: email.receivedAt,
        latestSubject: normalizeSubject(email.subject) || email.subject,
      })
    }
  }

  return Array.from(groups.values()).sort(
    (a, b) => new Date(b.lastReceivedAt).getTime() - new Date(a.lastReceivedAt).getTime(),
  )
})

const companyOptions = computed<SelectOption[]>(() => {
  const companies = Array.from(new Set(sortedEmails.value.map((email) => companyForEmail(email)))).sort()
  return [{ value: 'all', label: 'All companies' }, ...companies.map((company) => ({ value: company, label: company }))]
})

const directionOptions: SelectOption[] = [
  { value: 'all', label: 'All directions' },
  { value: 'INCOMING', label: 'Incoming' },
  { value: 'OUTGOING', label: 'Outgoing' },
]

const aiOptions: SelectOption[] = [
  { value: 'all', label: 'All summaries' },
  { value: 'with-ai', label: 'With AI summary' },
  { value: 'without-ai', label: 'Without AI summary' },
]

const linkedOptions: SelectOption[] = [
  { value: 'all', label: 'All links' },
  { value: 'linked', label: 'Matched to application' },
  { value: 'unlinked', label: 'Unmatched only' },
]

const filteredGroups = computed(() => {
  const term = search.value.trim().toLowerCase()

  return groupedEmails.value.filter((group) => {
    const matchesSearch = !term || [group.label, group.secondary, ...group.emails.map((email) => `${email.subject} ${email.body} ${email.aiSummary || ''}`)]
      .join(' ')
      .toLowerCase()
      .includes(term)

    const matchesDirection = directionFilter.value === 'all' || group.emails.some((email) => email.direction === directionFilter.value)
    const matchesAi = aiFilter.value === 'all'
      || (aiFilter.value === 'with-ai' && group.emails.some((email) => Boolean(email.aiSummary)))
      || (aiFilter.value === 'without-ai' && group.emails.every((email) => !email.aiSummary))
    const hasLinkedApplication = group.emails.some((email) => Boolean(guessLinkedApplication(email)))
    const matchesLinked = linkedFilter.value === 'all'
      || (linkedFilter.value === 'linked' && hasLinkedApplication)
      || (linkedFilter.value === 'unlinked' && !hasLinkedApplication)
    const matchesCompany = companyFilter.value === 'all' || group.label === companyFilter.value

    return matchesSearch && matchesDirection && matchesAi && matchesLinked && matchesCompany
  })
})

const emailsInSelectedGroup = computed(() => {
  const currentGroup = filteredGroups.value.find((group) => group.key === selectedGroupKey.value)
  return currentGroup?.emails || filteredGroups.value[0]?.emails || []
})

const selectedEmail = computed<RecruiterEmail | null>(() => {
  if (!emailsInSelectedGroup.value.length) return null

  const current = emailsInSelectedGroup.value.find((email) => email.id === selectedEmailId.value)
  return current ?? emailsInSelectedGroup.value[0] ?? null
})

const linkedApplication = computed(() => guessLinkedApplication(selectedEmail.value))

const statusVariant = (status: Application['status']) => {
  if (status === 'rejected') return 'destructive'
  if (status === 'accepted' || status === 'offer') return 'success'
  if (status === 'interview') return 'warning'
  if (status === 'wishlist') return 'secondary'
  return 'info'
}

watch(filteredGroups, (groups) => {
  if (!groups.length) {
    selectedGroupKey.value = null
    selectedEmailId.value = null
    return
  }

  if (!groups.some((group) => group.key === selectedGroupKey.value)) {
    selectedGroupKey.value = groups[0]?.key || null
  }
}, { immediate: true })

watch(emailsInSelectedGroup, (emails) => {
  if (!emails.length) {
    selectedEmailId.value = null
    return
  }

  if (!emails.some((email) => email.id === selectedEmailId.value)) {
    selectedEmailId.value = emails[0]?.id || null
  }
}, { immediate: true })

onMounted(() => {
  emailsStore.fetchEmails()
  if (!applications.value.length) {
    applicationsStore.fetchApplications()
  }
})
</script>

<template>
  <div class="flex h-full min-h-[calc(100vh-10rem)] flex-col gap-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Emails</h1>
        <p class="text-muted-foreground">Simple candidate mailbox, Apple Mail style</p>
      </div>
      <Button variant="outline" :disabled="isLoading" @click="emailsStore.fetchEmails()">
        <RefreshCw :size="16" :class="{ 'animate-spin': isLoading }" />
        Refresh
      </Button>
    </div>

    <div v-if="error" class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
      {{ error }}
    </div>

    <div class="grid flex-1 gap-4 lg:grid-cols-[360px_minmax(0,1fr)]">
      <Card class="flex min-h-0 flex-col overflow-hidden">
        <CardHeader class="border-b border-border pb-4">
          <CardTitle class="text-lg">Company Inbox</CardTitle>
          <div class="relative mt-3">
            <Search :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" placeholder="Search company or message" class="pl-9" />
          </div>
          <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <Select v-model="directionFilter" :options="directionOptions" />
            <Select v-model="aiFilter" :options="aiOptions" />
            <Select v-model="linkedFilter" :options="linkedOptions" />
            <Select v-model="companyFilter" :options="companyOptions" />
          </div>
        </CardHeader>
        <CardContent class="min-h-0 flex-1 overflow-y-auto p-0">
          <div v-if="isLoading" class="p-4 text-sm text-muted-foreground">Loading emails...</div>
          <button
            v-for="group in filteredGroups"
            :key="group.key"
            type="button"
            class="relative flex w-full flex-col gap-2 border-b border-border border-l-[8px] border-l-transparent px-5 py-4 text-left transition-colors hover:bg-accent/40"
            :class="selectedGroupKey === group.key ? 'border-l-emerald-700 bg-emerald-700/12' : ''"
            @click="selectedGroupKey = group.key"
          >
            <div class="flex items-start justify-between gap-3">
              <span class="truncate text-sm font-semibold">{{ group.label }}</span>
              <span class="shrink-0 text-xs text-muted-foreground">{{ format(new Date(group.lastReceivedAt), 'dd/MM') }}</span>
            </div>
            <div class="flex items-center justify-between gap-3">
              <div class="truncate text-sm font-medium text-muted-foreground">{{ group.latestSubject }}</div>
              <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <span>{{ group.emails.length }}</span>
                <ChevronRight :size="14" />
              </div>
            </div>
            <div class="line-clamp-2 text-xs text-muted-foreground">
              {{ group.emails[0]?.sender }} - {{ previewBody(group.emails[0]?.body || '') }}
            </div>
          </button>

          <div v-if="!isLoading && filteredGroups.length === 0" class="p-6 text-sm text-muted-foreground">
            No mailbox threads found.
          </div>
        </CardContent>
      </Card>

      <Card class="flex min-h-0 flex-col overflow-hidden">
        <template v-if="selectedEmail">
          <CardHeader class="border-b border-border">
            <div class="flex items-start justify-between gap-4">
              <div class="space-y-2">
                <CardTitle class="text-2xl leading-tight">{{ selectedEmail.subject }}</CardTitle>
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                  <MailOpen :size="16" />
                  <span>{{ selectedEmail.sender }}</span>
                </div>
                <div v-if="linkedApplication" class="flex flex-wrap items-center gap-2 pt-1 text-sm">
                  <div class="inline-flex items-center gap-2 rounded-full bg-secondary/70 px-3 py-1.5 text-foreground">
                    <Briefcase :size="14" />
                    <span>{{ linkedApplication.jobTitle }}</span>
                    <span class="text-muted-foreground">@ {{ linkedApplication.companyName }}</span>
                  </div>
                  <Badge :variant="statusVariant(linkedApplication.status)">{{ linkedApplication.status }}</Badge>
                  <div v-if="linkedApplication.jobOffer?.recruiterContactEmail" class="text-xs text-muted-foreground">
                    recruiter: {{ linkedApplication.jobOffer.recruiterContactEmail }}
                  </div>

                  <RouterLink :to="`/applications/${linkedApplication.id}`" class="text-xs text-muted-foreground flex items-center gap-2">
                    <div class="inline-flex items-center gap-2 rounded-full bg-[#e0f7fa] px-3 py-1.5 text-primary">
                      <ExternalLink :size="14" />
                      <span>View application in Job Planner</span>
                    </div>
                  </RouterLink>
                </div>
                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">
                  {{ emailsInSelectedGroup.length }} message{{ emailsInSelectedGroup.length > 1 ? 's' : '' }} in this thread
                </div>
              </div>
              <div class="text-right text-sm text-muted-foreground">
                <div>{{ format(new Date(selectedEmail.receivedAt), 'PPP') }}</div>
                <div>{{ format(new Date(selectedEmail.receivedAt), 'p') }}</div>
              </div>
            </div>
          </CardHeader>

          <CardContent class="flex-1 overflow-y-auto space-y-6 p-6">
            <div v-if="selectedEmail.aiSummary && selectedEmail.body.length > 200" class="flex items-start gap-3 rounded-xl border border-border bg-accent/40 p-4 text-sm text-muted-foreground bg-[#161632] text-white">
              
              <div>
                <div class="mb-1 font-medium text-[#01B79D] flex gap-2"> <Sparkles :size="16" class="mt-0.5 shrink-0" />AI summary</div>
                <div>{{ selectedEmail.aiSummary }}</div>
              </div>
            </div>

            <div class="whitespace-pre-wrap text-sm leading-7 text-foreground">
              {{ selectedEmail.body }}
            </div>

            <div v-if="emailsInSelectedGroup.length > 1" class="space-y-3 rounded-[1.25rem] bg-secondary/45 border rounded-none p-2">
              <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Thread timeline</div>
              <div
                v-for="threadEmail in emailsInSelectedGroup"
                :key="threadEmail.id"
                class="rounded-xl border border-border/70 bg-card/70 p-3"
              >
                <div class="flex items-center justify-between gap-3 text-xs text-muted-foreground">
                  <span>{{ threadEmail.sender }}</span>
                  <span>{{ format(new Date(threadEmail.receivedAt), 'PP p') }}</span>
                </div>
                <div class="mt-2 text-sm font-medium text-foreground">{{ threadEmail.subject }}</div>
                <div class="mt-2 line-clamp-2 text-sm text-muted-foreground">{{ previewBody(threadEmail.aiSummary || threadEmail.body) }}</div>
              </div>
            </div>
          </CardContent>
        </template>

        <CardContent v-else class="flex flex-1 items-center justify-center text-sm text-muted-foreground">
          Select a message to read it.
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

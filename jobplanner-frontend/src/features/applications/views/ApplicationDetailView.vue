<script setup lang="ts">
import { computed, ref, watch } from 'vue'
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
  ScrollText,
  Sparkles,
  Target,
} from 'lucide-vue-next'
import { useApplicationsStore } from '../stores/applications.store'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import ApplicationFormDialog from '../components/ApplicationFormDialog.vue'
import { interviewsApi } from '@/features/interviews/api/interviews.api'
import { extractIdFromIri } from '@/lib/api/transforms'
import type { Application, Interview } from '@/types/models.types'

const route = useRoute()
const router = useRouter()
const applicationsStore = useApplicationsStore()
const { currentApplication, isLoading } = storeToRefs(applicationsStore)

const isDialogOpen = ref(false)
const detailedInterviews = ref<Interview[]>([])

const applicationId = computed(() => String(route.params.id))

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

watch(applicationId, () => {
  loadApplication()
}, { immediate: true })
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
          <h1 class="display-title text-4xl font-semibold">{{ currentApplication.jobTitle }}</h1>
          <p class="mt-2 text-muted-foreground">{{ currentApplication.companyName }}</p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <Badge :variant="statusVariant(currentApplication.status)">{{ statusLabel(currentApplication.status) }}</Badge>
        <Button @click="isDialogOpen = true">Edit application</Button>
      </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(340px,0.75fr)]">
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2"><Building2 :size="18" /> Overview</CardTitle>
          </CardHeader>
          <CardContent class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl bg-secondary/50 p-4">
              <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Company</div>
              <div class="mt-2 text-lg font-semibold">{{ currentApplication.companyName }}</div>
            </div>
            <div class="rounded-2xl bg-secondary/50 p-4">
              <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">Pipeline position</div>
              <div class="mt-2 text-lg font-semibold">{{ currentApplication.pipelinePosition }}</div>
            </div>
            <div v-if="currentApplication.location" class="rounded-2xl bg-secondary/50 p-4">
              <div class="flex items-center gap-2 text-xs uppercase tracking-[0.16em] text-muted-foreground"><MapPin :size="14" /> Location</div>
              <div class="mt-2 text-base font-medium">{{ currentApplication.location }}</div>
            </div>
            <div v-if="currentApplication.appliedAt" class="rounded-2xl bg-secondary/50 p-4">
              <div class="flex items-center gap-2 text-xs uppercase tracking-[0.16em] text-muted-foreground"><CalendarDays :size="14" /> Applied at</div>
              <div class="mt-2 text-base font-medium">{{ formatDate(currentApplication.appliedAt) }}</div>
            </div>
            <div v-if="currentApplication.url" class="rounded-2xl bg-secondary/50 p-4 md:col-span-2">
              <div class="flex items-center gap-2 text-xs uppercase tracking-[0.16em] text-muted-foreground"><ExternalLink :size="14" /> Job URL</div>
              <a :href="currentApplication.url" target="_blank" rel="noreferrer" class="mt-2 inline-flex items-center gap-2 text-base font-medium text-primary hover:underline">
                Open posting
                <ExternalLink :size="14" />
              </a>
            </div>
          </CardContent>
        </Card>

        <Card v-if="currentApplication.notes">
          <CardHeader>
            <CardTitle class="flex items-center gap-2"><ScrollText :size="18" /> Notes</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="whitespace-pre-wrap text-sm leading-7 text-foreground">{{ currentApplication.notes }}</div>
          </CardContent>
        </Card>

        <Card v-if="currentApplication.jobOffer?.interviewPrep">
          <CardHeader>
            <CardTitle class="flex items-center gap-2"><Target :size="18" /> Interview prep</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="whitespace-pre-wrap text-sm leading-7 text-foreground">{{ currentApplication.jobOffer.interviewPrep }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Recruiter Emails</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div v-if="!currentApplication.recruiterEmails?.length" class="text-sm text-muted-foreground">No recruiter emails yet.</div>
            <div
              v-for="email in currentApplication.recruiterEmails"
              :key="email.id"
              class="rounded-[1.25rem] border border-border/80 bg-card/70 p-4"
            >
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="font-medium text-foreground">{{ email.subject }}</div>
                  <div class="mt-1 flex items-center gap-2 text-sm text-muted-foreground"><Mail :size="14" /> {{ email.sender }}</div>
                </div>
                <div class="text-xs text-muted-foreground">{{ format(new Date(email.receivedAt), 'PP p') }}</div>
              </div>
              <div class="mt-3 whitespace-pre-wrap text-sm leading-6 text-foreground">{{ email.body }}</div>
              <div v-if="email.aiSummary" class="mt-3 rounded-xl bg-accent/40 p-3 text-sm text-muted-foreground">
                <div class="mb-1 flex items-center gap-2 font-medium text-foreground"><Sparkles :size="14" /> AI Summary</div>
                {{ email.aiSummary }}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Application Metadata</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4 text-sm">
            <div class="flex items-center justify-between"><span class="text-muted-foreground">Created</span><strong>{{ formatDateTime(currentApplication.createdAt) }}</strong></div>
            <div v-if="currentApplication.lastActivityAt" class="flex items-center justify-between"><span class="text-muted-foreground">Last activity</span><strong>{{ formatDateTime(currentApplication.lastActivityAt) }}</strong></div>
            <div v-if="currentApplication.jobOffer?.sourceUrl" class="space-y-2">
              <div class="text-muted-foreground">Source URL</div>
              <a :href="currentApplication.jobOffer.sourceUrl" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 text-primary hover:underline">
                {{ currentApplication.jobOffer.sourceUrl }}
              </a>
            </div>
            <div v-if="currentApplication.jobOffer?.recruiterContactEmail" class="space-y-2">
              <div class="text-muted-foreground">Recruiter contact</div>
              <div class="font-medium">{{ currentApplication.jobOffer.recruiterContactEmail }}</div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Interviews</CardTitle>
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

        <Card>
          <CardHeader>
            <CardTitle>Scheduled Follow-ups</CardTitle>
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

        <Card>
          <CardHeader>
            <CardTitle>History</CardTitle>
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

    <ApplicationFormDialog
      :open="isDialogOpen"
      :application="currentApplication"
      @update:open="isDialogOpen = $event"
      @success="loadApplication"
    />
  </div>

  <div v-else class="flex min-h-[320px] items-center justify-center text-muted-foreground">
    {{ isLoading ? 'Loading application...' : 'Application not found.' }}
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useApplicationsStore } from '../stores/applications.store'
import type { Application } from '@/types/models.types'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import KanbanColumn from '../components/KanbanColumn.vue'
import ApplicationFormDialog from '../components/ApplicationFormDialog.vue'
import { Plus, RefreshCw } from 'lucide-vue-next'

const applicationsStore = useApplicationsStore()
const router = useRouter()
const { applicationsByStatus, isLoading } = storeToRefs(applicationsStore)

const isDialogOpen = ref(false)
const editingApplication = ref<Application | null>(null)
const isInterviewDialogOpen = ref(false)
const interviewApplicationId = ref<number | null>(null)
const interviewScheduledAt = ref('')
const interviewError = ref('')
const interviewMoveCommitted = ref(false)
const interviewApplicationsSnapshot = ref<Application[]>([])

const handleNewApplication = (): void => {
  editingApplication.value = null
  isDialogOpen.value = true
}

const handleDialogSuccess = (): void => {
  applicationsStore.fetchApplications()
}

const handleApplicationClick = (application: Application): void => {
  router.push({ name: 'ApplicationDetail', params: { id: application.id } })
}

const openInterviewDialog = (applicationId: number): void => {
  interviewApplicationId.value = applicationId
  interviewScheduledAt.value = ''
  interviewError.value = ''
  interviewMoveCommitted.value = false
  interviewApplicationsSnapshot.value = applicationsStore.applications.map((application) => ({
    ...application,
    jobOffer: { ...application.jobOffer },
  }))
  isInterviewDialogOpen.value = true
}

const resetInterviewDialog = (): void => {
  isInterviewDialogOpen.value = false
  interviewApplicationId.value = null
  interviewScheduledAt.value = ''
  interviewError.value = ''
}

const handleCancelInterviewDialog = async (): Promise<void> => {
  const shouldRestoreBoard = interviewApplicationId.value !== null && !interviewMoveCommitted.value

  resetInterviewDialog()

  if (shouldRestoreBoard) {
    applicationsStore.setApplications(
      interviewApplicationsSnapshot.value.map((application) => ({
        ...application,
        jobOffer: { ...application.jobOffer },
      })),
    )
  }

  interviewApplicationsSnapshot.value = []
}

const handleInterviewDialogOpenChange = (open: boolean): void => {
  if (!open) {
    void handleCancelInterviewDialog()
    return
  }

  isInterviewDialogOpen.value = true
}

const handleConfirmInterviewDate = async (): Promise<void> => {
  if (!interviewApplicationId.value) return

  if (!interviewScheduledAt.value) {
    interviewError.value = 'Please choose the interview date and time.'
    return
  }

  const scheduledAt = new Date(interviewScheduledAt.value)
  if (Number.isNaN(scheduledAt.getTime())) {
    interviewError.value = 'Please choose a valid interview date and time.'
    return
  }

  try {
    await applicationsStore.moveApplicationToInterview(
      interviewApplicationId.value,
      scheduledAt.toISOString(),
    )
    interviewMoveCommitted.value = true
    resetInterviewDialog()
    interviewApplicationsSnapshot.value = []
  } catch (error) {
    console.error('Failed to schedule interview:', error)
    interviewError.value = 'Failed to schedule the interview. Please try again.'
  }
}


const columns: Array<{
  title: string
  status: Application['status']
}> = [
  { title: 'Wishlist', status: 'wishlist' },
  { title: 'Applied', status: 'applied' },
  { title: 'Interview', status: 'interview' },
  { title: 'Offer', status: 'offer' },
  { title: 'Rejected', status: 'rejected' },
  { title: 'Accepted', status: 'accepted' },
]

const handleMove = async (
  applicationId: number,
  _fromStatus: Application['status'],
  toStatus: Application['status'],
) => {
  try {
    if (toStatus === 'interview') {
      openInterviewDialog(applicationId)
      return
    }

    await applicationsStore.updateApplicationStatus(applicationId, toStatus)
  } catch (error) {
    console.error('Failed to update application status:', error)
  }
}

onMounted(() => {
  applicationsStore.fetchApplications()
})
</script>

<template>
  <div class="flex h-full flex-col gap-6">
    <Card>
      <CardContent class="flex flex-col gap-6 p-8 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="section-kicker mb-3">Pipeline studio</p>
        <h1 class="display-title text-4xl font-semibold">Applications</h1>
        <p class="mt-3 max-w-2xl text-sm text-muted-foreground">
          Move opportunities across your pipeline, open a card to refine details, and keep the board emotionally quiet.
        </p>
      </div>
      <div class="flex gap-2">
        <Button
          variant="outline"
          size="sm"
          :disabled="isLoading"
          @click="applicationsStore.fetchApplications()"
        >
          <RefreshCw :size="16" :class="{ 'animate-spin': isLoading }" />
          Refresh
        </Button>
        <Button size="sm" @click="handleNewApplication">
          <Plus :size="16" />
          New Application
        </Button>
      </div>
      </CardContent>
    </Card>

    <div
      v-if="isLoading && !applicationsByStatus"
      class="flex h-64 items-center justify-center"
    >
      <div class="flex items-center gap-2 text-muted-foreground">
        <RefreshCw :size="20" class="animate-spin" />
        <span>Loading applications...</span>
      </div>
    </div>

    <div
      v-else
      class="grid flex-1 grid-cols-1 gap-4 overflow-x-auto pb-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
    >
      <KanbanColumn
        v-for="column in columns"
        :key="column.status"
        :title="column.title"
        :status="column.status"
        :applications="applicationsByStatus[column.status] || []"
        @move="handleMove"
        @application-click="handleApplicationClick"
      />
    </div>

    <ApplicationFormDialog
      :open="isDialogOpen"
      :application="editingApplication"
      @update:open="isDialogOpen = $event"
      @success="handleDialogSuccess"
    />

    <Dialog :open="isInterviewDialogOpen" @update:open="handleInterviewDialogOpenChange">
      <DialogContent class="max-w-lg">
        <DialogHeader>
          <DialogTitle>Schedule interview</DialogTitle>
          <DialogDescription>
            Pick the interview date and time before moving the application to Interview.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-2 py-2">
          <Label for="interviewScheduledAt">Interview date and time</Label>
          <Input
            id="interviewScheduledAt"
            v-model="interviewScheduledAt"
            type="datetime-local"
          />
          <p v-if="interviewError" class="text-sm text-destructive">{{ interviewError }}</p>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="handleCancelInterviewDialog">
            Cancel
          </Button>
          <Button type="button" :disabled="isLoading" @click="handleConfirmInterviewDate">
            Save and move
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

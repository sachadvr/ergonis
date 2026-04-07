<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useApplicationsStore } from '../stores/applications.store'
import type { Application } from '@/types/models.types'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import KanbanColumn from '../components/KanbanColumn.vue'
import ApplicationFormDialog from '../components/ApplicationFormDialog.vue'
import { Plus, RefreshCw } from 'lucide-vue-next'

const applicationsStore = useApplicationsStore()
const router = useRouter()
const { applicationsByStatus, isLoading } = storeToRefs(applicationsStore)

// Dialog state
const isDialogOpen = ref(false)
const editingApplication = ref<Application | null>(null)

const handleNewApplication = (): void => {
  editingApplication.value = null
  isDialogOpen.value = true
}

const handleDialogSuccess = (): void => {
  // Refresh applications after successful create/update
  applicationsStore.fetchApplications()
}

const handleApplicationClick = (application: Application): void => {
  router.push({ name: 'ApplicationDetail', params: { id: application.id } })
}


// Column definitions
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

// Handle drag & drop move
const handleMove = async (
  applicationId: number,
  _fromStatus: Application['status'],
  toStatus: Application['status'],
) => {
  try {
    await applicationsStore.updateApplicationStatus(applicationId, toStatus)
  } catch (error) {
    console.error('Failed to update application status:', error)
  }
}

// Fetch applications on mount
onMounted(() => {
  applicationsStore.fetchApplications()
})
</script>

<template>
  <div class="flex h-full flex-col gap-6">
    <!-- Header -->
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

    <!-- Loading State -->
    <div
      v-if="isLoading && !applicationsByStatus"
      class="flex h-64 items-center justify-center"
    >
      <div class="flex items-center gap-2 text-muted-foreground">
        <RefreshCw :size="20" class="animate-spin" />
        <span>Loading applications...</span>
      </div>
    </div>

    <!-- Kanban Board -->
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

    <!-- Application Form Dialog -->
    <ApplicationFormDialog
      :open="isDialogOpen"
      :application="editingApplication"
      @update:open="isDialogOpen = $event"
      @success="handleDialogSuccess"
    />
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

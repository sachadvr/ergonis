<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, type SelectOption } from '@/components/ui/select'
import { useApplicationsStore } from '@/features/applications/stores/applications.store'
import type { Application, ApplicationFormValues } from '@/types/models.types'
import { APPLICATION_STATUSES } from '@/lib/constants/app.constants'
import { Trash2 } from 'lucide-vue-next'

interface ApplicationFormDialogProps {
  open: boolean
  application?: Application | null
}

const props = withDefaults(defineProps<ApplicationFormDialogProps>(), {
  open: false,
  application: null
})

const emit = defineEmits<{
  'update:open': [value: boolean]
  'success': []
}>()

const applicationsStore = useApplicationsStore()
const { isLoading } = storeToRefs(applicationsStore)

const formData = ref<ApplicationFormValues>({
  jobTitle: '',
  companyName: '',
  location: '',
  status: 'wishlist',
  jobUrl: '',
  salaryMin: undefined,
  salaryMax: undefined,
  notes: '',
  appliedAt: undefined,
  recruiterContactEmail: '',
  sourceUrl: '',
  interviewPrep: '',
})

const errors = ref<Record<string, string>>({})
const isSubmitting = ref(false)

const statusOptions: SelectOption[] = [
  { value: APPLICATION_STATUSES[0], label: 'Wishlist' },
  { value: APPLICATION_STATUSES[1], label: 'Applied' },
  { value: APPLICATION_STATUSES[2], label: 'Interview' },
  { value: APPLICATION_STATUSES[3], label: 'Offer' },
  { value: APPLICATION_STATUSES[4], label: 'Rejected' },
  { value: APPLICATION_STATUSES[5], label: 'Accepted' },
]

const isEditMode = computed(() => !!props.application)
const dialogTitle = computed(() => isEditMode.value ? 'Edit Application' : 'New Application')
const dialogDescription = computed(() => 
  isEditMode.value 
    ? 'Update the details of your job application.' 
    : 'Add a new job application to track your progress.'
)

watch(() => props.application, (newApplication) => {
  if (newApplication) {
    formData.value = {
      jobTitle: newApplication.jobTitle,
      companyName: newApplication.companyName,
      location: newApplication.location || '',
      status: newApplication.status,
      jobUrl: newApplication.url || '',
      salaryMin: newApplication.salaryMin,
      salaryMax: newApplication.salaryMax,
      notes: newApplication.notes || '',
      appliedAt: newApplication.appliedAt,
      pipelinePosition: newApplication.pipelinePosition,
      recruiterContactEmail: newApplication.jobOffer?.recruiterContactEmail || '',
      sourceUrl: newApplication.jobOffer?.sourceUrl || '',
      interviewPrep: newApplication.jobOffer?.interviewPrep || '',
    }
  }
}, { immediate: true })

watch(() => props.open, (isOpen) => {
  if (!isOpen) {
    resetForm()
  }
})

const resetForm = (): void => {
  formData.value = {
    jobTitle: '',
    companyName: '',
    location: '',
    status: 'wishlist',
    jobUrl: '',
    salaryMin: undefined,
    salaryMax: undefined,
    notes: '',
    appliedAt: undefined,
    pipelinePosition: 0,
    recruiterContactEmail: '',
    sourceUrl: '',
    interviewPrep: '',
  }
  errors.value = {}
}

const validateForm = (): boolean => {
  errors.value = {}

  if (!formData.value.jobTitle.trim()) {
    errors.value.jobTitle = 'Job title is required'
  }

  if (!formData.value.companyName.trim()) {
    errors.value.companyName = 'Company name is required'
  }

  if (formData.value.salaryMin !== undefined && formData.value.salaryMax !== undefined) {
    if (formData.value.salaryMin > formData.value.salaryMax) {
      errors.value.salaryMin = 'Minimum salary cannot be greater than maximum'
    }
  }

  if (formData.value.jobUrl && !isValidUrl(formData.value.jobUrl)) {
    errors.value.jobUrl = 'Please enter a valid URL'
  }

  return Object.keys(errors.value).length === 0
}

const isValidUrl = (url: string): boolean => {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
}

const handleSubmit = async (): Promise<void> => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true

  try {
    if (isEditMode.value && props.application) {
      await applicationsStore.updateApplication(
        props.application.id,
        formData.value,
        props.application.jobOffer?.id,
      )
    } else {
      await applicationsStore.createApplication(formData.value)
    }

    emit('update:open', false)
    emit('success')
  } catch (error) {
    console.error('Failed to submit application:', error)
    errors.value.submit = 'Failed to save application. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}

const handleCancel = (): void => {
  emit('update:open', false)
}

const handleDelete = async (): Promise<void> => {
  if (!props.application) return

  isSubmitting.value = true

  try {
    await applicationsStore.deleteApplication(props.application.id)
    emit('update:open', false)
    emit('success')
  } catch (error) {
    console.error('Failed to delete application:', error)
    errors.value.submit = 'Failed to delete application. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <Dialog :open="props.open" @update:open="(value) => emit('update:open', value)">
    <DialogContent class="max-h-[85vh] max-w-2xl overflow-hidden p-0">
      <DialogHeader class="border-b border-border px-6 py-5 pr-12">
        <DialogTitle>{{ dialogTitle }}</DialogTitle>
        <DialogDescription>{{ dialogDescription }}</DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="flex max-h-[calc(85vh-88px)] flex-col">
        <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
        <div class="space-y-2">
          <Label for="jobTitle">Job Title *</Label>
          <Input
            id="jobTitle"
            v-model="formData.jobTitle"
            placeholder="e.g. Senior Frontend Developer"
            :class="errors.jobTitle ? 'border-red-500' : ''"
          />
          <p v-if="errors.jobTitle" class="text-sm text-red-500">{{ errors.jobTitle }}</p>
        </div>

        <div class="space-y-2">
          <Label for="companyName">Company Name *</Label>
          <Input
            id="companyName"
            v-model="formData.companyName"
            placeholder="e.g. Acme Corp"
            :class="errors.companyName ? 'border-red-500' : ''"
          />
          <p v-if="errors.companyName" class="text-sm text-red-500">{{ errors.companyName }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <Label for="location">Location</Label>
            <Input
              id="location"
              v-model="formData.location"
              placeholder="e.g. Paris, France"
            />
          </div>

          <div class="space-y-2">
            <Label for="status">Status *</Label>
            <Select
              id="status"
              v-model="formData.status"
              :options="statusOptions"
              placeholder="Select status"
            />
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <Label for="salaryMin">Minimum Salary (€)</Label>
            <Input
              id="salaryMin"
              v-model.number="formData.salaryMin"
              type="number"
              placeholder="e.g. 50000"
              :class="errors.salaryMin ? 'border-red-500' : ''"
            />
            <p v-if="errors.salaryMin" class="text-sm text-red-500">{{ errors.salaryMin }}</p>
          </div>

          <div class="space-y-2">
            <Label for="salaryMax">Maximum Salary (€)</Label>
            <Input
              id="salaryMax"
              v-model.number="formData.salaryMax"
              type="number"
              placeholder="e.g. 70000"
            />
          </div>
        </div>

        <div class="space-y-2">
          <Label for="jobUrl">Job URL</Label>
          <Input
            id="jobUrl"
            v-model="formData.jobUrl"
            type="url"
            placeholder="https://example.com/job-posting"
            :class="errors.jobUrl ? 'border-red-500' : ''"
          />
          <p v-if="errors.jobUrl" class="text-sm text-red-500">{{ errors.jobUrl }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <Label for="sourceUrl">Source URL</Label>
            <Input
              id="sourceUrl"
              v-model="formData.sourceUrl"
              type="url"
              placeholder="https://linkedin.com/jobs/..."
            />
          </div>

          <div class="space-y-2">
            <Label for="recruiterContactEmail">Recruiter Email</Label>
            <Input
              id="recruiterContactEmail"
              v-model="formData.recruiterContactEmail"
              type="email"
              placeholder="recruiter@company.com"
            />
          </div>
        </div>

        <div class="space-y-2">
          <Label for="notes">Notes</Label>
          <Textarea
            id="notes"
            v-model="formData.notes"
            placeholder="Add any additional notes or observations..."
            :rows="4"
          />
        </div>

        <div class="space-y-2">
          <Label for="interviewPrep">Interview Prep</Label>
          <Textarea
            id="interviewPrep"
            v-model="formData.interviewPrep"
            placeholder="Preparation notes, likely questions, talking points..."
            :rows="4"
          />
        </div>

        <p v-if="errors.submit" class="text-sm text-red-500">{{ errors.submit }}</p>
        </div>

        <DialogFooter class="border-t border-border px-6 py-4">
          <Button
            v-if="isEditMode"
            type="button"
            variant="ghost"
            class="mr-auto text-destructive hover:bg-destructive/10 hover:text-destructive"
            :disabled="isSubmitting"
            @click="handleDelete"
          >
            <Trash2 :size="16" />
            Delete
          </Button>
          <Button
            type="button"
            variant="outline"
            @click="handleCancel"
            :disabled="isSubmitting"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="isSubmitting || isLoading"
          >
            {{ isSubmitting ? 'Saving...' : isEditMode ? 'Update' : 'Create' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

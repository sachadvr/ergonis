<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { format } from 'date-fns'
import { CalendarDays, Clock3, FileText } from 'lucide-vue-next'
import { useInterviewsStore } from '../stores/interviews.store'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const interviewsStore = useInterviewsStore()
const { sortedInterviews, isLoading, error } = storeToRefs(interviewsStore)

const typeLabel = computed(() => (type: string) => {
  if (type === 'visio') return 'Visio'
  if (type === 'tel') return 'Telephone'
  if (type === 'presentiel') return 'On-site'
  return type
})

onMounted(() => {
  interviewsStore.fetchInterviews()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="section-kicker mb-3">Conversation calendar</p>
        <h1 class="display-title text-4xl font-semibold">Interviews</h1>
        <p class="mt-2 text-muted-foreground">A calm timeline of every scheduled conversation.</p>
      </div>
      <Button variant="outline" :disabled="isLoading" @click="interviewsStore.fetchInterviews()">
        Refresh
      </Button>
    </div>

    <div v-if="error" class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
      {{ error }}
    </div>

    <div v-if="isLoading" class="text-sm text-muted-foreground">Loading interviews...</div>

    <div v-else class="space-y-4">
      <Card v-for="interview in sortedInterviews" :key="interview.id" class="border-border/70 bg-card/85">
        <CardHeader class="flex flex-row items-center justify-between space-y-0">
          <CardTitle class="text-lg">Interview #{{ interview.id }}</CardTitle>
          <Badge variant="outline">{{ typeLabel(interview.type) }}</Badge>
        </CardHeader>
        <CardContent class="grid gap-3 text-sm md:grid-cols-2">
          <div class="flex items-center gap-2"><CalendarDays :size="16" /> {{ format(new Date(interview.scheduledAt), 'PPP') }}</div>
          <div class="flex items-center gap-2"><Clock3 :size="16" /> {{ format(new Date(interview.scheduledAt), 'p') }}</div>
          <div v-if="interview.locationOrLink" class="text-muted-foreground md:col-span-2">{{ interview.locationOrLink }}</div>
          <div v-if="interview.notes" class="flex items-start gap-2 text-muted-foreground md:col-span-2"><FileText :size="16" class="mt-0.5" /> {{ interview.notes }}</div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

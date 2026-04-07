<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import {
  addMonths,
  eachDayOfInterval,
  endOfMonth,
  endOfWeek,
  format,
  isSameDay,
  isSameMonth,
  startOfMonth,
  startOfWeek,
  subMonths,
} from 'date-fns'
import { CalendarDays, ChevronLeft, ChevronRight, Clock3, FileText } from 'lucide-vue-next'
import { useInterviewsStore } from '../stores/interviews.store'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const interviewsStore = useInterviewsStore()
const { sortedInterviews, isLoading, error } = storeToRefs(interviewsStore)
const showOverdue = ref(false)
const currentMonth = ref(startOfMonth(new Date()))

const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

const upcomingInterviews = computed(() => {
  const currentTimestamp = Date.now()

  return sortedInterviews.value.filter((interview) => {
    return new Date(interview.scheduledAt).getTime() >= currentTimestamp
  })
})

const overdueInterviews = computed(() => {
  const currentTimestamp = Date.now()

  return sortedInterviews.value.filter((interview) => {
    return new Date(interview.scheduledAt).getTime() < currentTimestamp
  })
})

const panelInterviews = computed(() => {
  return showOverdue.value ? overdueInterviews.value : upcomingInterviews.value
})

const panelTitle = computed(() => {
  return showOverdue.value ? 'Overdue interviews' : 'Next interviews'
})

const calendarDays = computed(() => {
  const monthStart = startOfMonth(currentMonth.value)
  const monthEnd = endOfMonth(currentMonth.value)
  const calendarStart = startOfWeek(monthStart, { weekStartsOn: 1 })
  const calendarEnd = endOfWeek(monthEnd, { weekStartsOn: 1 })

  return eachDayOfInterval({ start: calendarStart, end: calendarEnd })
})

const upcomingInterviewsByDay = computed(() => {
  const grouped = new Map<string, typeof upcomingInterviews.value>()

  upcomingInterviews.value.forEach((interview) => {
    const key = format(new Date(interview.scheduledAt), 'yyyy-MM-dd')
    const existing = grouped.get(key) ?? []
    grouped.set(key, [...existing, interview])
  })

  return grouped
})

function isToday(day: Date): boolean {
  return isSameDay(day, new Date())
}

function interviewsForDay(day: Date) {
  return upcomingInterviewsByDay.value.get(format(day, 'yyyy-MM-dd')) ?? []
}

function previousMonth() {
  currentMonth.value = subMonths(currentMonth.value, 1)
}

function nextMonth() {
  currentMonth.value = addMonths(currentMonth.value, 1)
}

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
        <p class="mt-2 text-muted-foreground">A real calendar view for upcoming interviews.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button variant="outline" :disabled="isLoading" @click="showOverdue = !showOverdue">
          {{ showOverdue ? 'Hide overdue interviews' : 'Show overdue interviews' }}
        </Button>
        <Button variant="outline" :disabled="isLoading" @click="interviewsStore.fetchInterviews()">
          Refresh
        </Button>
      </div>
    </div>

    <div v-if="error" class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
      {{ error }}
    </div>

    <div v-if="isLoading" class="text-sm text-muted-foreground">Loading interviews...</div>

    <div v-else class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
      <Card class="border-border/70 bg-card/85">
        <CardHeader class="flex flex-row items-center justify-between space-y-0">
          <CardTitle class="text-lg">{{ format(currentMonth, 'MMMM yyyy') }}</CardTitle>
          <div class="flex items-center gap-2">
            <Button variant="outline" size="icon" @click="previousMonth">
              <ChevronLeft :size="16" />
            </Button>
            <Button variant="outline" size="icon" @click="nextMonth">
              <ChevronRight :size="16" />
            </Button>
          </div>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="grid grid-cols-7 gap-2 text-center text-xs font-medium uppercase tracking-wide text-muted-foreground">
            <div v-for="weekDay in weekDays" :key="weekDay">{{ weekDay }}</div>
          </div>

          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-7">
            <div
              v-for="day in calendarDays"
              :key="format(day, 'yyyy-MM-dd')"
              class="min-h-28 rounded-lg border border-border/70 bg-card/70 p-2"
              :class="{
                'opacity-45': !isSameMonth(day, currentMonth),
                'ring-1 ring-primary/40': isToday(day),
              }"
            >
              <div class="mb-2 text-xs font-semibold" :class="isToday(day) ? 'text-primary' : 'text-muted-foreground'">
                {{ format(day, 'd') }}
              </div>

              <div class="space-y-1">
                <div
                  v-for="interview in interviewsForDay(day)"
                  :key="interview.id"
                  class="rounded-md border border-border/70 bg-card px-2 py-1 text-xs"
                >
                  <div class="flex items-center justify-between gap-2">
                    <span class="truncate font-medium">{{ typeLabel(interview.type) }}</span>
                    <span class="text-muted-foreground">{{ format(new Date(interview.scheduledAt), 'p') }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="upcomingInterviews.length === 0" class="rounded-lg border border-border/70 bg-card/60 p-4 text-sm text-muted-foreground">
            No upcoming interviews.
          </div>
        </CardContent>
      </Card>

      <Card class="border-border/70 bg-card/85 xl:sticky xl:top-6 xl:self-start">
        <CardHeader>
          <CardTitle class="text-lg">{{ panelTitle }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div v-if="panelInterviews.length === 0" class="rounded-lg border border-border/70 bg-card/60 p-4 text-sm text-muted-foreground">
            {{ showOverdue ? 'No overdue interviews.' : 'No upcoming interviews.' }}
          </div>

          <div v-else class="space-y-3 xl:max-h-[60vh] xl:overflow-auto xl:pr-1">
            <Card v-for="interview in panelInterviews" :key="interview.id" class="border-border/70 bg-card">
              <CardHeader class="flex flex-row items-center justify-between space-y-0">
                <CardTitle class="text-base">Interview #{{ interview.id }}</CardTitle>
                <div class="flex items-center gap-2">
                  <Badge v-if="showOverdue" variant="outline" class="border-destructive/50 text-destructive">Overdue</Badge>
                  <Badge variant="outline">{{ typeLabel(interview.type) }}</Badge>
                </div>
              </CardHeader>
              <CardContent class="grid gap-3 text-sm md:grid-cols-2">
                <div class="flex items-center gap-2"><CalendarDays :size="16" /> {{ format(new Date(interview.scheduledAt), 'PPP') }}</div>
                <div class="flex items-center gap-2"><Clock3 :size="16" /> {{ format(new Date(interview.scheduledAt), 'p') }}</div>
                <div v-if="interview.locationOrLink" class="text-muted-foreground md:col-span-2">{{ interview.locationOrLink }}</div>
                <div v-if="interview.notes" class="flex items-start gap-2 text-muted-foreground md:col-span-2"><FileText :size="16" class="mt-0.5" /> {{ interview.notes }}</div>
              </CardContent>
            </Card>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

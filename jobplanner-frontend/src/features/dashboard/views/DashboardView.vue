<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useApplicationsStore } from '@/features/applications/stores/applications.store'
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import type { BadgeVariants } from '@/components/ui/badge/badge.variants'
import { ArrowRight, Briefcase, Calendar, ChevronRight, Flame, Sparkles, Target, Clock3, BarChart3 } from 'lucide-vue-next'

const router = useRouter()
const applicationsStore = useApplicationsStore()
const { applications, stats, isLoading } = storeToRefs(applicationsStore)

const sortedApplications = computed(() => {
  return [...applications.value]
    .sort((a, b) => {
      const dateA = new Date(a.lastActivityAt || a.updatedAt || a.createdAt || 0).getTime()
      const dateB = new Date(b.lastActivityAt || b.updatedAt || b.createdAt || 0).getTime()
      return dateB - dateA
    })
})

// Computed: Active applications (applied + interview)
const activeCount = computed(() => stats.value.applied + stats.value.interview)

const daysSince = (value?: string) => {
  if (!value) return Number.POSITIVE_INFINITY

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return Number.POSITIVE_INFINITY

  return Math.max(0, Math.floor((Date.now() - date.getTime()) / 86400000))
}

const urgencyScore = (status: string, lastActivityAt?: string, createdAt?: string) => {
  const age = daysSince(lastActivityAt || createdAt)
  const statusBoost: Record<string, number> = {
    wishlist: 0,
    applied: 3,
    interview: 1,
    offer: -6,
    rejected: -10,
    accepted: -10,
  }

  return Math.max(0, Math.round(age * 1.8 + (statusBoost[status] ?? 0)))
}

const urgencyLabel = (score: number) => {
  if (score >= 14) return 'hot'
  if (score >= 8) return 'watch'
  return 'fresh'
}

const activityAgeLabel = (value?: string) => {
  const age = daysSince(value)
  if (!Number.isFinite(age)) return 'No activity'
  if (age === 0) return 'Today'
  if (age === 1) return '1 day idle'
  return `${age} days idle`
}

const urgencyTone = (score: number): BadgeVariants['variant'] => {
  if (score >= 14) return 'destructive'
  if (score >= 8) return 'info'
  return 'secondary'
}

const pipelineHealth = computed(() => {
  const total = stats.value.total || 1

  return [
    { label: 'Wishlist', value: stats.value.wishlist, tone: 'bg-secondary' },
    { label: 'Applied', value: stats.value.applied, tone: 'bg-sky-500/70' },
    { label: 'Interview', value: stats.value.interview, tone: 'bg-sky-600/70' },
    { label: 'Offer', value: stats.value.offer, tone: 'bg-emerald-500/70' },
  ].map((item) => ({
    ...item,
    width: `${Math.max((item.value / total) * 100, item.value > 0 ? 10 : 0)}%`,
  }))
})

const funnelStages = computed(() => {
  return [
    { label: 'Wishlist', value: stats.value.wishlist },
    { label: 'Applied', value: stats.value.applied },
    { label: 'Interview', value: stats.value.interview },
    { label: 'Offer', value: stats.value.offer },
  ].map((stage, index, stages) => ({
    ...stage,
    ratio: stats.value.total ? Math.round((stage.value / stats.value.total) * 100) : 0,
    connector: index < stages.length - 1 ? Math.max(Math.round(((stages[index + 1]?.value ?? 0) / (stage.value || 1)) * 100), 0) : null,
  }))
})

const urgencyQueue = computed(() => {
  return sortedApplications.value
    .map((application) => ({
      ...application,
      urgencyScore: urgencyScore(application.status, application.lastActivityAt, application.createdAt),
    }))
    .sort((a, b) => b.urgencyScore - a.urgencyScore)
    .slice(0, 5)
})

const activityTimeline = computed(() => {
  return sortedApplications.value.slice(0, 6).map((application) => {
    const date = application.lastActivityAt || application.updatedAt || application.createdAt
    const score = urgencyScore(application.status, application.lastActivityAt, application.createdAt)

    return {
      id: application.id,
      title: application.jobTitle,
      company: application.companyName,
      date: formatShortDate(date),
      status: application.status,
      urgencyScore: score,
      urgencyLabel: urgencyLabel(score),
      tone: statusTone(application.status),
    }
  })
})

const staleCount = computed(() => {
  return applications.value.filter((application) => daysSince(application.lastActivityAt || application.updatedAt || application.createdAt) >= 7).length
})

const averageAge = computed(() => {
  if (applications.value.length === 0) return 0

  const ages = applications.value.map((application) => daysSince(application.lastActivityAt || application.updatedAt || application.createdAt))
  const total = ages.reduce((sum, age) => sum + (Number.isFinite(age) ? age : 0), 0)

  return Math.round(total / applications.value.length)
})

const statusTone = (status: string): BadgeVariants['variant'] => {
  switch (status) {
    case 'rejected':
      return 'destructive'
    case 'accepted':
    case 'offer':
      return 'success'
    case 'interview':
      return 'info'
    case 'applied':
      return 'info'
    default:
      return 'secondary'
  }
}

const statusLabel = (status: string) => status.replace(/_/g, ' ').replace(/\b\w/g, (char: string) => char.toUpperCase())

const formatShortDate = (value?: string) => {
  if (!value) return 'No date'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'No date'

  return new Intl.DateTimeFormat('en', {
    month: 'short',
    day: 'numeric',
  }).format(date)
}

const openApplications = () => {
  router.push({ name: 'Applications' })
}

const openInterviews = () => {
  router.push({ name: 'Interviews' })
}

// Fetch applications on mount
onMounted(() => {
  if (applications.value.length === 0) {
    applicationsStore.fetchApplications()
  }
})
</script>

<template>
  <div class="space-y-6 lg:space-y-8">
    <Card class="overflow-hidden border-border/70">
      <CardContent class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(340px,0.85fr)] lg:p-10">
        <div class="space-y-5">
          <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">
            <Sparkles :size="14" class="text-sky-600" />
            Dashboard
          </div>
          <h1 class="display-title max-w-3xl text-4xl font-semibold leading-[0.95] sm:text-5xl lg:text-6xl">
            A sharper command center for your search.
          </h1>
          <p class="max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">
            Funnel, urgency, and timeline. See where the pipeline stalls, what needs attention now, and what moved recently.
          </p>

          <div class="flex flex-wrap gap-3 pt-1">
            <Button @click="openApplications">
              <Briefcase :size="16" class="mr-2" />
              Open applications
            </Button>
            <Button variant="secondary" @click="openInterviews">
              <Calendar :size="16" class="mr-2" />
              Review interviews
            </Button>
          </div>

          <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-[1.25rem] border border-border/70 bg-background/80 px-4 py-3">
              <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Stale</div>
              <div class="mt-2 flex items-end justify-between">
                <div class="text-2xl font-semibold text-foreground">{{ staleCount }}</div>
                <Clock3 :size="16" class="text-sky-600" />
              </div>
            </div>
            <div class="rounded-[1.25rem] border border-border/70 bg-background/80 px-4 py-3">
              <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Avg age</div>
              <div class="mt-2 flex items-end justify-between">
                <div class="text-2xl font-semibold text-foreground">{{ averageAge }}d</div>
                <BarChart3 :size="16" class="text-sky-600" />
              </div>
            </div>
            <div class="rounded-[1.25rem] border border-border/70 bg-background/80 px-4 py-3">
              <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Active</div>
              <div class="mt-2 flex items-end justify-between">
                <div class="text-2xl font-semibold text-foreground">{{ isLoading ? '...' : activeCount }}</div>
                <Target :size="16" class="text-sky-600" />
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-[1.75rem] border border-border/70 bg-card/85 p-5 shadow-sm backdrop-blur-sm">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Funnel</div>
              <div class="mt-2 text-lg font-semibold text-foreground">Conversion flow</div>
            </div>
            <BarChart3 :size="20" class="text-sky-600" />
          </div>

          <div class="mt-5 h-3 overflow-hidden rounded-full bg-secondary/70">
            <div class="flex h-full w-full gap-1">
              <div
                v-for="item in pipelineHealth"
                :key="item.label"
                class="rounded-full transition-all"
                :class="item.tone"
                :style="{ width: item.width }"
              />
            </div>
          </div>

          <div class="mt-5 grid gap-3">
            <div
              v-for="stage in funnelStages"
              :key="stage.label"
              class="flex items-center justify-between rounded-2xl border border-border/70 bg-background/80 px-4 py-3"
            >
              <div>
                <div class="font-medium text-foreground">{{ stage.label }}</div>
                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">{{ stage.ratio }}% of total</div>
              </div>
              <div class="text-right">
                <div class="text-lg font-semibold text-foreground">{{ stage.value }}</div>
                <div class="text-xs uppercase tracking-[0.16em] text-muted-foreground">count</div>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <Card class="border-border/70">
        <CardContent class="p-5">
          <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Total tracked</span>
            <Briefcase :size="18" class="text-sky-600" />
          </div>
          <div class="mt-4 text-3xl font-semibold tracking-tight">{{ isLoading ? '...' : stats.total }}</div>
          <div class="mt-2 text-sm text-muted-foreground">All tracked opportunities</div>
        </CardContent>
      </Card>

      <Card class="border-border/70">
        <CardContent class="p-5">
          <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Interview-ready</span>
            <Calendar :size="18" class="text-sky-600" />
          </div>
          <div class="mt-4 text-3xl font-semibold tracking-tight text-foreground">{{ isLoading ? '...' : stats.interview }}</div>
          <div class="mt-2 text-sm text-muted-foreground">Upcoming conversations</div>
        </CardContent>
      </Card>

      <Card class="border-border/70 sm:col-span-2 xl:col-span-1">
        <CardContent class="p-5">
          <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Offers</span>
            <Flame :size="18" class="text-sky-600" />
          </div>
          <div class="mt-4 text-3xl font-semibold tracking-tight text-foreground">{{ isLoading ? '...' : stats.offer }}</div>
          <div class="mt-2 text-sm text-muted-foreground">Converted opportunities</div>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(340px,0.95fr)]">
      <Card class="border-border/70">
        <CardHeader class="flex flex-row items-center justify-between gap-4 pb-4">
          <div>
            <p class="section-kicker mb-2">Activity</p>
            <CardTitle class="display-title text-2xl font-semibold">Latest timeline</CardTitle>
          </div>
          <Button variant="ghost" size="sm" @click="router.push({ name: 'Applications' })">
            View all
            <ArrowRight :size="16" class="ml-1" />
          </Button>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="text-sm text-muted-foreground">
            Loading applications...
          </div>
          <div v-else-if="activityTimeline.length === 0" class="rounded-2xl border border-dashed border-border/80 bg-background/70 p-6 text-sm text-muted-foreground">
            No activity yet. Start by adding your first application.
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="item in activityTimeline"
              :key="item.id"
              class="group flex cursor-pointer gap-4 rounded-[1.35rem] border border-border/70 bg-background/80 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/20 hover:bg-primary-light/70 hover:shadow-sm"
              @click="router.push({ name: 'ApplicationDetail', params: { id: item.id } })"
            >
              <div class="relative flex flex-col items-center pt-1">
                <span class="h-3 w-3 rounded-full" :class="item.tone === 'destructive' ? 'bg-destructive' : item.tone === 'info' ? 'bg-sky-500' : item.tone === 'success' ? 'bg-emerald-500' : 'bg-secondary-foreground/50'" />
                <span class="mt-2 h-full w-px bg-border/80" />
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="truncate font-medium text-foreground">{{ item.title }}</div>
                    <div class="truncate text-sm text-muted-foreground">{{ item.company }}</div>
                  </div>
                  <Badge :variant="item.tone">{{ statusLabel(item.status) }}</Badge>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                  <span class="inline-flex items-center gap-1.5">
                    <Clock3 :size="14" />
                    {{ item.date }}
                  </span>
                  <span class="rounded-full bg-sky-500/10 px-2.5 py-1 font-medium uppercase tracking-[0.14em] text-sky-700">
                    {{ item.urgencyLabel }}
                  </span>
                  <span class="text-[11px] uppercase tracking-[0.16em]">{{ item.urgencyScore }} urgency</span>
                </div>
              </div>

              <ChevronRight :size="16" class="mt-1 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="border-border/70">
        <CardHeader class="flex flex-row items-center justify-between gap-4 pb-4">
          <div>
            <p class="section-kicker mb-2">Priority queue</p>
            <CardTitle class="display-title text-2xl font-semibold">Next to handle</CardTitle>
          </div>
           <Flame :size="18" class="text-sky-600" />
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="text-sm text-muted-foreground">
            Loading queue...
          </div>
          <div v-else-if="urgencyQueue.length === 0" class="rounded-2xl border border-dashed border-border/80 bg-background/70 p-6 text-sm text-muted-foreground">
            No urgent applications right now.
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="application in urgencyQueue"
              :key="application.id"
              class="rounded-[1.35rem] border border-border/70 bg-background/80 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-sky-500/20 hover:bg-sky-500/5"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <div class="truncate font-medium text-foreground">{{ application.jobTitle }}</div>
                  <div class="truncate text-sm text-muted-foreground">{{ application.companyName }}</div>
                </div>
                <Badge :variant="urgencyTone(application.urgencyScore)">{{ urgencyLabel(application.urgencyScore) }}</Badge>
              </div>

              <div class="mt-3 flex items-center justify-between text-xs text-muted-foreground">
                <span>{{ activityAgeLabel(application.lastActivityAt || application.updatedAt || application.createdAt) }}</span>
                <span class="font-medium text-foreground">Score {{ application.urgencyScore }}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

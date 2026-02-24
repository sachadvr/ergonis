<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useApplicationsStore } from '@/features/applications/stores/applications.store'
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { ArrowRight, TrendingUp, Briefcase, Calendar, CheckCircle } from 'lucide-vue-next'

const router = useRouter()
const applicationsStore = useApplicationsStore()
const { applications, stats, isLoading } = storeToRefs(applicationsStore)

// Computed: Recent applications (last 5)
const recentApplications = computed(() => {
  return [...applications.value]
    .sort((a, b) => {
      const dateA = new Date(a.createdAt || 0).getTime()
      const dateB = new Date(b.createdAt || 0).getTime()
      return dateB - dateA
    })
    .slice(0, 5)
})

// Computed: Active applications (applied + interview)
const activeCount = computed(() => stats.value.applied + stats.value.interview)

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
  <div class="space-y-8">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.7fr)]">
      <Card class="overflow-hidden">
        <CardContent class="grid gap-6 p-8 lg:grid-cols-[1fr_auto] lg:items-end">
          <div>
            <p class="section-kicker mb-3">Search rhythm</p>
            <h1 class="display-title text-5xl font-semibold leading-none">A sharper view of <span class="text-[#171733] underline">your pipeline.</span></h1>
            <p class="mt-4 max-w-2xl text-base text-muted-foreground">
              Track momentum, recent movement, and the applications that deserve your next decision.
            </p>
          </div>
          <div class="rounded-[1.75rem] bg-primary px-6 py-5 text-primary-foreground shadow-[0_20px_50px_rgba(27,97,103,0.22)]">
            <div class="text-xs uppercase tracking-[0.18em] text-primary-foreground/75">Active now</div>
            <div class="mt-3 text-5xl font-semibold">{{ isLoading ? '...' : activeCount }}</div>
            <div class="mt-2 text-sm text-primary-foreground/80">live conversations in motion</div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="text-xl">At a glance</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4 text-sm text-muted-foreground">
          <div class="flex items-center justify-between rounded-2xl bg-secondary/60 px-4 py-3">
            <span>Total tracked</span>
            <strong class="text-foreground">{{ isLoading ? '...' : stats.total }}</strong>
          </div>
          <div class="flex items-center justify-between rounded-2xl bg-secondary/60 px-4 py-3">
            <span>Interview-ready</span>
            <strong class="text-foreground">{{ isLoading ? '...' : stats.interview }}</strong>
          </div>
          <div class="flex items-center justify-between rounded-2xl bg-secondary/60 px-4 py-3">
            <span>Offers received</span>
            <strong class="text-foreground">{{ isLoading ? '...' : stats.offer }}</strong>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
      <Card
        class="cursor-pointer border-l-[6px] border-l-primary transition-colors hover:bg-accent/25 focus-within:ring-2 focus-within:ring-primary/30"
        role="button"
        tabindex="0"
        @click="openApplications"
        @keydown.enter="openApplications"
        @keydown.space.prevent="openApplications"
      >
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-muted-foreground">Total Applications</div>
            <Briefcase :size="20" class="text-muted-foreground" />
          </div>
          <div class="mt-2 text-3xl font-bold">{{ isLoading ? '...' : stats.total }}</div>
          <div class="mt-2 text-xs text-muted-foreground">All time</div>
        </CardContent>
      </Card>

      <Card
        class="cursor-pointer border-l-[6px] border-l-sky-800/70 transition-colors hover:bg-accent/25 focus-within:ring-2 focus-within:ring-primary/30"
        role="button"
        tabindex="0"
        @click="openApplications"
        @keydown.enter="openApplications"
        @keydown.space.prevent="openApplications"
      >
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-muted-foreground">Active</div>
            <TrendingUp :size="20" class="text-blue-500" />
          </div>
          <div class="mt-2 text-3xl font-bold text-blue-500">{{ isLoading ? '...' : activeCount }}</div>
          <div class="mt-2 text-xs text-muted-foreground">Applied & Interview</div>
        </CardContent>
      </Card>

      <Card
        class="cursor-pointer border-l-[6px] border-l-amber-700/70 transition-colors hover:bg-accent/25 focus-within:ring-2 focus-within:ring-primary/30"
        role="button"
        tabindex="0"
        @click="openInterviews"
        @keydown.enter="openInterviews"
        @keydown.space.prevent="openInterviews"
      >
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-muted-foreground">Interviews</div>
            <Calendar :size="20" class="text-yellow-500" />
          </div>
          <div class="mt-2 text-3xl font-bold text-yellow-500">{{ isLoading ? '...' : stats.interview }}</div>
          <div class="mt-2 text-xs text-muted-foreground">Scheduled</div>
        </CardContent>
      </Card>

      <Card
        class="cursor-pointer border-l-[6px] border-l-emerald-800/70 transition-colors hover:bg-accent/25 focus-within:ring-2 focus-within:ring-primary/30"
        role="button"
        tabindex="0"
        @click="openApplications"
        @keydown.enter="openApplications"
        @keydown.space.prevent="openApplications"
      >
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-muted-foreground">Offers</div>
            <CheckCircle :size="20" class="text-green-500" />
          </div>
          <div class="mt-2 text-3xl font-bold text-green-500">{{ isLoading ? '...' : stats.offer }}</div>
          <div class="mt-2 text-xs text-muted-foreground">Received</div>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader class="flex flex-row items-center justify-between">
        <div>
          <p class="section-kicker mb-2">Recent movement</p>
          <CardTitle>Recent Applications</CardTitle>
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
        <div v-else-if="recentApplications.length === 0" class="text-sm text-muted-foreground">
          No applications yet. Start by adding your first application!
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="application in recentApplications"
            :key="application.id"
            class="flex cursor-pointer items-start justify-between gap-4 rounded-[1.25rem] border border-border/80 bg-card/70 p-4 transition-colors hover:bg-accent/35"
            @click="router.push({ name: 'ApplicationDetail', params: { id: application.id } })"
          >
            <div class="flex-1 space-y-1">
              <div class="font-medium text-foreground">{{ application.jobTitle }}</div>
              <div class="text-sm text-muted-foreground">{{ application.companyName }}</div>
            </div>
            <Badge
              :variant="
                application.status === 'rejected'
                  ? 'destructive'
                  : application.status === 'accepted'
                    ? 'success'
                    : application.status === 'offer'
                      ? 'success'
                      : application.status === 'interview'
                        ? 'warning'
                        : 'info'
              "
            >
              {{ application.status }}
            </Badge>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

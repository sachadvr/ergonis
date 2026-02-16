<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { ExternalLink, MapPin, Building2, Mail } from 'lucide-vue-next'
import { useJobOffersStore } from '../stores/job-offers.store'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'

const jobOffersStore = useJobOffersStore()
const { sortedJobOffers, isLoading, error } = storeToRefs(jobOffersStore)

onMounted(() => {
  jobOffersStore.fetchJobOffers()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="section-kicker mb-3">Opportunity library</p>
        <h1 class="display-title text-4xl font-semibold">Job Offers</h1>
        <p class="mt-2 text-muted-foreground">A curated board of tracked roles and recruiter entry points.</p>
      </div>
      <Button variant="outline" :disabled="isLoading" @click="jobOffersStore.fetchJobOffers()">
        Refresh
      </Button>
    </div>

    <div v-if="error" class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
      {{ error }}
    </div>

    <div v-if="isLoading" class="text-sm text-muted-foreground">Loading job offers...</div>

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <Card v-for="offer in sortedJobOffers" :key="offer.id" class="border-border/70 bg-card/85">
        <CardHeader>
          <CardTitle class="flex items-start justify-between gap-3 text-lg">
            <span>{{ offer.title }}</span>
            <a v-if="offer.url" :href="offer.url" target="_blank" rel="noreferrer" class="text-muted-foreground hover:text-foreground">
              <ExternalLink :size="16" />
            </a>
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-3 text-sm">
          <div class="flex items-center gap-2"><Building2 :size="16" /> {{ offer.company }}</div>
          <div v-if="offer.location" class="flex items-center gap-2 text-muted-foreground"><MapPin :size="16" /> {{ offer.location }}</div>
          <div v-if="offer.recruiterContactEmail" class="flex items-center gap-2 text-muted-foreground"><Mail :size="16" /> {{ offer.recruiterContactEmail }}</div>
          <p v-if="offer.notes" class="text-muted-foreground">{{ offer.notes }}</p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

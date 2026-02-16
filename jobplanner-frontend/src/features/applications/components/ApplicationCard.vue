<script setup lang="ts">
import { computed } from 'vue'
import { format } from 'date-fns'
import type { Application } from '@/types/models.types'
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Building2, Calendar, MapPin, DollarSign } from 'lucide-vue-next'

interface ApplicationCardProps {
  application: Application
}

const props = defineProps<ApplicationCardProps>()

const emit = defineEmits<{
  'click': [application: Application]
}>()

const handleClick = (): void => {
  emit('click', props.application)
}

// Get badge variant based on status
const statusVariant = computed(() => {
  const variants: Record<Application['status'], 'default' | 'secondary' | 'success' | 'warning' | 'info' | 'destructive'> = {
    wishlist: 'secondary',
    applied: 'info',
    interview: 'warning',
    offer: 'success',
    rejected: 'destructive',
    accepted: 'success',
  }
  return variants[props.application.status]
})

// Format status for display
const statusLabel = computed(() => {
  const labels: Record<Application['status'], string> = {
    wishlist: 'Wishlist',
    applied: 'Applied',
    interview: 'Interview',
    offer: 'Offer',
    rejected: 'Rejected',
    accepted: 'Accepted',
  }
  return labels[props.application.status]
})

// Format date
const formattedDate = computed(() => {
  if (!props.application.appliedAt) return null
  return format(new Date(props.application.appliedAt), 'MMM d, yyyy')
})

// Format salary
const formattedSalary = computed(() => {
  if (!props.application.salaryMin && !props.application.salaryMax) return null
  
  const min = props.application.salaryMin?.toLocaleString()
  const max = props.application.salaryMax?.toLocaleString()
  
  if (min && max) return `$${min} - $${max}`
  if (min) return `From $${min}`
  if (max) return `Up to $${max}`
  return null
})
</script>

<template>
  <Card 
    class="group cursor-pointer border-border/70 bg-card/85 transition-all duration-200 hover:-translate-y-1 hover:border-primary/40 hover:shadow-[0_18px_28px_rgba(64,56,45,0.10)]" 
    @click="handleClick"
  >
    <CardHeader class="pb-3">
      <div class="flex items-start justify-between gap-2">
        <CardTitle class="text-base leading-tight">
          {{ application.jobTitle }}
        </CardTitle>
        <Badge :variant="statusVariant" class="shrink-0">
          {{ statusLabel }}
        </Badge>
      </div>
    </CardHeader>

    <CardContent class="space-y-3">
      <!-- Company -->
      <div class="flex items-center gap-2 text-sm text-muted-foreground">
        <Building2 :size="16" class="shrink-0" />
        <span class="truncate">{{ application.companyName }}</span>
      </div>

      <!-- Location -->
      <div v-if="application.location" class="flex items-center gap-2 text-sm text-muted-foreground">
        <MapPin :size="16" class="shrink-0" />
        <span class="truncate">{{ application.location }}</span>
      </div>

      <!-- Salary -->
      <div v-if="formattedSalary" class="flex items-center gap-2 text-sm text-muted-foreground">
        <DollarSign :size="16" class="shrink-0" />
        <span>{{ formattedSalary }}</span>
      </div>

      <!-- Applied Date -->
      <div v-if="formattedDate" class="flex items-center gap-2 text-sm text-muted-foreground">
        <Calendar :size="16" class="shrink-0" />
        <span>{{ formattedDate }}</span>
      </div>

      <!-- Notes Preview -->
      <div v-if="application.notes" class="mt-3 border-t border-border/70 pt-3">
        <p class="text-xs text-muted-foreground line-clamp-2">
          {{ application.notes }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

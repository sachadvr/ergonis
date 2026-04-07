<script setup lang="ts">
import { computed } from 'vue'
import { format, isWithinInterval, subDays } from 'date-fns'
import type { Application } from '@/types/models.types'
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Building2, Calendar, MapPin, DollarSign, Sparkles } from 'lucide-vue-next'

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
const companyToneClass = computed(() => {
  const tones: Record<Application['status'], string> = {
    wishlist: 'bg-secondary text-secondary-foreground',
    applied: 'bg-sky-500/12 text-sky-700',
    interview: 'bg-violet-500/12 text-violet-700',
    offer: 'bg-emerald-500/12 text-emerald-700',
    rejected: 'bg-rose-500/12 text-rose-700',
    accepted: 'bg-emerald-500/12 text-emerald-700',
  }

  return tones[props.application.status]
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
const isNew = computed(() => {
  if (!props.application.createdAt) return false
  return isWithinInterval(new Date(props.application.createdAt), { start: subDays(new Date(), 1), end: new Date() })
})
</script>

<template>
  <Card 
    class="group cursor-pointer border-border/70 bg-card/85 transition-all duration-200 hover:-translate-y-1 hover:border-primary/40 hover:shadow-[0_18px_28px_rgba(15,23,42,0.08)]" 
    @click="handleClick"
  >
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-2 relative">
        <div class="flex items-start justify-between gap-2 relative">
          <Badge variant="outline" :class="['min-w-0 px-2.5 py-1 text-[11px]', companyToneClass]">
            <Building2 :size="14" class="shrink-0" />
            <span class="whitespace-normal break-words">{{ application.companyName }}</span>
          </Badge>
          
        </div>
        <CardTitle class="text-base leading-tight">
          {{ application.jobTitle }}
        </CardTitle>
      </div>
    </CardHeader>
    
    <CardContent class="space-y-3">
      <!-- Company -->
      
      
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
      
      <span v-if="isNew" class="text-red-500 bg-red-500/30 text-white text-xs px-2 py-1 rounded-full flex gap-2 w-fit"><Sparkles :size="16" class="shrink-0" /> New</span>
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

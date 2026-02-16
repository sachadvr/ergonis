<script setup lang="ts">
import { ref, computed } from 'vue'
import { useSortable } from '@vueuse/integrations/useSortable'
import type { Application } from '@/types/models.types'
import { Badge } from '@/components/ui/badge'
import ApplicationCard from './ApplicationCard.vue'

interface KanbanColumnProps {
  title: string
  status: Application['status']
  applications: Application[]
  color?: string
}

const props = withDefaults(defineProps<KanbanColumnProps>(), {
  color: 'default',
})

const emit = defineEmits<{
  move: [applicationId: number, fromStatus: Application['status'], toStatus: Application['status']]
  'application-click': [application: Application]
}>()

const handleApplicationClick = (application: Application): void => {
  emit('application-click', application)
}

const columnRef = ref<HTMLElement | null>(null)

// Color variants for column headers
const colorVariants: Record<string, string> = {
  wishlist: 'bg-stone-500/10 text-stone-700',
  applied: 'bg-sky-800/10 text-sky-800',
  interview: 'bg-amber-700/10 text-amber-800',
  offer: 'bg-teal-800/10 text-teal-800',
  rejected: 'bg-rose-700/10 text-rose-800',
  accepted: 'bg-emerald-800/10 text-emerald-800',
}

const headerColorClass = computed(() => colorVariants[props.status] || colorVariants.default)

// Initialize sortable for drag & drop
useSortable(columnRef, props.applications, {
  animation: 200,
  group: 'kanban',
  ghostClass: 'opacity-30',
  dragClass: 'rotate-2',
  onAdd: (event) => {
    const applicationId = event.item.dataset.id
    const fromStatus = event.from.dataset.status as Application['status']
    const toStatus = event.to.dataset.status as Application['status']
    
    if (applicationId && fromStatus && toStatus && fromStatus !== toStatus) {
      emit('move', Number(applicationId), fromStatus, toStatus)
    }
  },
})
</script>

<template>
  <div class="flex h-full flex-col rounded-[1.75rem] border border-border/80 bg-card/70 shadow-sm">
    <!-- Column Header -->
    <div class="flex items-center justify-between gap-2 border-b border-border/80 px-5 py-4">
      <div class="flex items-center gap-2">
        <h3 class="text-sm font-semibold tracking-wide">{{ title }}</h3>
        <Badge :class="headerColorClass" variant="secondary" class="h-5 min-w-[24px] justify-center px-1.5">
          {{ applications.length }}
        </Badge>
      </div>
    </div>

    <!-- Cards Container -->
    <div
      ref="columnRef"
      :data-status="status"
      class="flex-1 space-y-3 overflow-y-auto p-4"
    >
      <div
        v-for="application in applications"
        :key="application.id"
        :data-id="application.id"
        class="kanban-item"
      >
        <ApplicationCard 
          :application="application" 
          @click="handleApplicationClick"
        />
      </div>

      <!-- Empty State -->
      <div
        v-if="applications.length === 0"
        class="flex h-32 items-center justify-center rounded-[1.25rem] border-2 border-dashed border-border/60 bg-secondary/30 text-sm text-muted-foreground"
      >
        Drop applications here
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar for the column */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: hsl(var(--border));
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--muted-foreground));
}
</style>

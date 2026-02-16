<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils/cn'

interface CardHeaderProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<CardHeaderProps>()

const classes = computed(() =>
  cn('flex flex-col space-y-1.5 p-6', props.class),
)
</script>

<template>
  <div :class="classes">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils/cn'

interface CardProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<CardProps>()

const classes = computed(() =>
  cn(
    'paper-panel rounded-[1.5rem] border border-border/80 bg-card text-card-foreground shadow-sm',
    props.class,
  ),
)
</script>

<template>
  <div :class="classes">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils/cn'

interface CardContentProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<CardContentProps>()

const classes = computed(() => cn('p-6 pt-0', props.class))
</script>

<template>
  <div :class="classes">
    <slot />
  </div>
</template>

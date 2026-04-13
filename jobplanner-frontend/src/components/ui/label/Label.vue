<script setup lang="ts">
import { Label as RadixLabel } from 'radix-vue'
import { cn } from '@/lib/utils/cn'

interface LabelProps {
  for?: string
  class?: string
}

const props = withDefaults(defineProps<LabelProps>(), {
  class: ''
})
</script>

<template>
  <RadixLabel
    :for="props.for"
    :class="cn(
      'text-sm font-medium leading-none text-foreground',
      'peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
      props.class
    )"
  >
    <slot />
  </RadixLabel>
</template>

<script setup lang="ts">
import { cn } from '@/lib/utils/cn'

interface DialogFooterProps {
  class?: string
}

const props = withDefaults(defineProps<DialogFooterProps>(), {
  class: ''
})
</script>

<template>
  <div
    :class="cn('flex flex-col-reverse sm:flex-row sm:justify-end sm:gap-2', props.class)"
  >
    <slot />
  </div>
</template>

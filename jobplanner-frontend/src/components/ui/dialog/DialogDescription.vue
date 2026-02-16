<script setup lang="ts">
import { DialogDescription as RadixDialogDescription } from 'radix-vue'
import { cn } from '@/lib/utils/cn'

interface DialogDescriptionProps {
  class?: string
}

const props = withDefaults(defineProps<DialogDescriptionProps>(), {
  class: ''
})
</script>

<template>
  <RadixDialogDescription
    :class="cn('text-sm leading-6 text-muted-foreground', props.class)"
  >
    <slot />
  </RadixDialogDescription>
</template>

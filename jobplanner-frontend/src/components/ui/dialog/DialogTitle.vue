<script setup lang="ts">
import { DialogTitle as RadixDialogTitle } from 'radix-vue'
import { cn } from '@/lib/utils/cn'

interface DialogTitleProps {
  class?: string
}

const props = withDefaults(defineProps<DialogTitleProps>(), {
  class: ''
})
</script>

<template>
  <RadixDialogTitle
    :class="cn('display-title text-2xl font-semibold leading-none tracking-tight text-foreground', props.class)"
  >
    <slot />
  </RadixDialogTitle>
</template>

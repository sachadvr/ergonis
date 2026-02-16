<script setup lang="ts">
import { cn } from '@/lib/utils/cn'

interface DialogHeaderProps {
  class?: string
}

const props = withDefaults(defineProps<DialogHeaderProps>(), {
  class: ''
})
</script>

<template>
  <div :class="cn('flex flex-col gap-2', props.class)">
    <slot />
  </div>
</template>

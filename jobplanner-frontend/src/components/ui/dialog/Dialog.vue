<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { DialogRoot, type DialogRootEmits, type DialogRootProps } from 'radix-vue'

interface DialogProps extends DialogRootProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<DialogProps>()
const emit = defineEmits<DialogRootEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props
  return delegated
})
</script>

<template>
  <DialogRoot v-bind="delegatedProps" @update:open="emit('update:open', $event)">
    <slot />
  </DialogRoot>
</template>

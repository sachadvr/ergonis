<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import {
  DialogClose,
  DialogContent,
  DialogOverlay,
  DialogPortal,
  type DialogContentEmits,
  type DialogContentProps,
} from 'radix-vue'
import { X } from 'lucide-vue-next'
import { cn } from '@/lib/utils/cn'

interface Props extends DialogContentProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()
const emit = defineEmits<DialogContentEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props
  return delegated
})
</script>

<template>
  <DialogPortal>
    <DialogOverlay
      class="fixed inset-0 z-50 bg-[rgba(15,23,42,0.32)] backdrop-blur-[3px] data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
    />
    <DialogContent
      v-bind="delegatedProps"
      :class="
        cn(
          'fixed left-[50%] top-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 overflow-hidden rounded-xl border border-border/80 bg-card/98 text-foreground shadow-[0_24px_60px_rgba(15,23,42,0.14)] duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%]',
          props.class,
        )
      "
      @escape-key-down="emit('escapeKeyDown', $event)"
      @pointer-down-outside="emit('pointerDownOutside', $event)"
      @focus-outside="emit('focusOutside', $event)"
      @interact-outside="emit('interactOutside', $event)"
      @open-auto-focus="emit('openAutoFocus', $event)"
      @close-auto-focus="emit('closeAutoFocus', $event)"
    >
      <slot />

      <DialogClose
        class="absolute right-4 top-4 rounded-full bg-secondary/70 p-2 text-muted-foreground opacity-90 ring-offset-background transition-all hover:bg-primary-light hover:text-foreground hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none"
      >
        <X :size="16" />
        <span class="sr-only">Close</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>

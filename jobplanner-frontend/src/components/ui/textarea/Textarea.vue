<script setup lang="ts">
import { cn } from '@/lib/utils/cn'

interface TextareaProps {
  modelValue?: string
  placeholder?: string
  disabled?: boolean
  rows?: number
  class?: string
}

const props = withDefaults(defineProps<TextareaProps>(), {
  modelValue: '',
  placeholder: '',
  disabled: false,
  rows: 3,
  class: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const handleInput = (event: Event): void => {
  const target = event.target as HTMLTextAreaElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <textarea
    :value="props.modelValue"
    :placeholder="props.placeholder"
    :disabled="props.disabled"
    :rows="props.rows"
    :class="cn(
      'flex min-h-[120px] w-full rounded-2xl border border-input/80 bg-card/85 px-4 py-3 text-sm text-foreground shadow-sm',
      'focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring',
      'placeholder:text-muted-foreground',
      'disabled:cursor-not-allowed disabled:opacity-50',
      'transition-colors duration-200',
      props.class
    )"
    @input="handleInput"
  />
</template>

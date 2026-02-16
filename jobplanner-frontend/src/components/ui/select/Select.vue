<script setup lang="ts">
import {
  SelectRoot,
  SelectTrigger,
  SelectValue,
  SelectIcon,
  SelectPortal,
  SelectContent,
  SelectViewport,
  SelectItem,
  SelectItemText,
  SelectItemIndicator
} from 'radix-vue'
import { ChevronDown, Check } from 'lucide-vue-next'
import { cn } from '@/lib/utils/cn'

interface SelectOption {
  value: string
  label: string
}

interface SelectProps {
  modelValue?: string
  options: SelectOption[]
  placeholder?: string
  disabled?: boolean
  class?: string
}

const props = withDefaults(defineProps<SelectProps>(), {
  modelValue: '',
  placeholder: 'Select an option',
  disabled: false,
  class: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const handleValueChange = (value: string): void => {
  emit('update:modelValue', value)
}
</script>

<template>
  <SelectRoot
    :model-value="props.modelValue"
    :disabled="props.disabled"
    @update:model-value="handleValueChange"
  >
    <SelectTrigger
      :class="cn(
        'flex h-11 w-full items-center justify-between rounded-2xl border border-input/80 bg-card/85 px-4 py-2 text-sm text-foreground shadow-sm',
        'focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring',
        'disabled:cursor-not-allowed disabled:opacity-50',
        'transition-colors duration-200',
        props.class
      )"
    >
      <SelectValue :placeholder="props.placeholder" />
      <SelectIcon>
        <ChevronDown :size="16" class="text-muted-foreground" />
      </SelectIcon>
    </SelectTrigger>

    <SelectPortal>
      <SelectContent
        :class="cn(
          'relative z-50 min-w-[12rem] overflow-hidden rounded-2xl border border-border bg-card text-foreground shadow-xl',
          'data-[state=open]:animate-in data-[state=closed]:animate-out',
          'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
          'data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95'
        )"
        position="popper"
        :side-offset="4"
      >
        <SelectViewport class="p-1">
          <SelectItem
            v-for="option in props.options"
            :key="option.value"
            :value="option.value"
            :class="cn(
              'relative flex w-full cursor-pointer select-none items-center rounded-xl py-2 pl-9 pr-3 text-sm outline-none',
              'focus:bg-accent/70 focus:text-accent-foreground',
              'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
              'transition-colors duration-150'
            )"
          >
            <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
              <SelectItemIndicator>
                <Check :size="14" class="text-primary" />
              </SelectItemIndicator>
            </span>
            <SelectItemText>
              {{ option.label }}
            </SelectItemText>
          </SelectItem>
        </SelectViewport>
      </SelectContent>
    </SelectPortal>
  </SelectRoot>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import { BubbleMenu, FloatingMenu } from '@tiptap/vue-3/menus'
import StarterKit from '@tiptap/starter-kit'
import Highlight from '@tiptap/extension-highlight'
import Placeholder from '@tiptap/extension-placeholder'

const props = withDefaults(
  defineProps<{
    modelValue: string
    placeholder: string
  }>(),
  {
    modelValue: '',
    placeholder: '',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  blur: []
}>()

const editor = useEditor({
  extensions: [
    StarterKit.configure({
      heading: { levels: [1, 2, 3] },
    }),
    Highlight,
    Placeholder.configure({
      placeholder: props.placeholder,
    }),
  ],
  content: props.modelValue || '',
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
  onBlur: () => {
    emit('blur')
  },
})

watch(
  [() => props.modelValue, editor],
  ([value, instance]) => {
    const nextValue = value || ''
    if (!instance) return
    if (instance.getHTML() !== nextValue) {
      instance.commands.setContent(nextValue, false)
    }
  },
  { immediate: true },
)

const canToggle = computed(() => !!editor.value)
</script>

<template>
  <div class="space-y-3">
    <BubbleMenu v-if="editor" :editor="editor" class="notion-bubble-menu">
      <button type="button" class="editor-action" :disabled="!canToggle" @mousedown.prevent @click="editor?.chain().focus().toggleBold().run()">Bold</button>
      <button type="button" class="editor-action" :disabled="!canToggle" @mousedown.prevent @click="editor?.chain().focus().toggleItalic().run()">Italic</button>
      <button type="button" class="editor-action" :disabled="!canToggle" @mousedown.prevent @click="editor?.chain().focus().toggleHighlight().run()">Highlight</button>
    </BubbleMenu>

    <FloatingMenu v-if="editor" :editor="editor" class="notion-floating-menu">
      <button type="button" class="editor-action" :disabled="!canToggle" @mousedown.prevent @click="editor?.chain().focus().setParagraph().run()">Text</button>
      <button type="button" class="editor-action" :disabled="!canToggle" @mousedown.prevent @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()">H1</button>
      <button type="button" class="editor-action" :disabled="!canToggle" @mousedown.prevent @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">H2</button>
      <button type="button" class="editor-action" :disabled="!canToggle" @mousedown.prevent @click="editor?.chain().focus().toggleBulletList().run()">List</button>
    </FloatingMenu>

    <EditorContent v-if="editor" :editor="editor" class="rich-editor" />
  </div>
</template>

<style scoped>
.editor-action {
  border: 1px solid hsl(var(--border));
  border-radius: 0.65rem;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.2rem 0.55rem;
}

.editor-action:hover {
  background: color-mix(in srgb, var(--accent) 80%, transparent);
}

.editor-action:disabled {
  cursor: default;
  opacity: 0.55;
}

:deep(.ProseMirror) {
  border: 1px solid color-mix(in srgb, var(--border) 70%, transparent);
  border-radius: 1rem;
  cursor: text;
  min-height: 10rem;
  outline: none;
  padding: 0.85rem 1rem;
}

:deep(.ProseMirror:focus) {
  border-color: color-mix(in srgb, var(--primary) 42%, transparent);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary) 25%, transparent);
}

:deep(.ProseMirror p) {
  margin: 0 0 0.75rem;
}

:deep(.ProseMirror p:last-child) {
  margin-bottom: 0;
}

:deep(.ProseMirror ul) {
  list-style: disc;
  padding-left: 1.25rem;
}

:deep(.ProseMirror li) {
  margin: 0.25rem 0;
}

:deep(.ProseMirror mark) {
  background: #fff2a8;
  border-radius: 0.2rem;
  box-decoration-break: clone;
  -webkit-box-decoration-break: clone;
}

.notion-bubble-menu,
.notion-floating-menu {
  align-items: center;
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: 0.9rem;
  box-shadow: 0 18px 40px rgba(74, 61, 39, 0.12);
  display: flex;
  gap: 0.35rem;
  padding: 0.4rem;
}

:deep(.is-empty::before) {
  color: hsl(var(--muted-foreground));
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}
</style>

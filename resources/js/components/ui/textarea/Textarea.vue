<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { useVModel } from '@vueuse/core'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
  defaultValue?: string | number
  modelValue?: string | number
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <textarea
    v-model="modelValue"
    data-slot="textarea"
    :class="cn('border-input placeholder:text-muted-foreground aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex field-sizing-content min-h-16 w-full rounded-lg border bg-gray-50/80 px-3 py-2 text-base shadow-xs outline-none transition-all duration-200 hover:border-gray-300 focus:border-primary focus:bg-white focus:ring-[3px] focus:ring-primary/[0.06] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm', props.class)"
  />
</template>

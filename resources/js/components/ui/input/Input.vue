<script setup lang="ts">
import { HTMLAttributes, ref, useAttrs } from 'vue';
import { useVModel } from '@vueuse/core'
import { cn } from '@/lib/utils'

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

const attrs = useAttrs()

const inputRef = ref<HTMLInputElement>()
defineExpose({ inputRef })
</script>

<template>
    <input
        v-model="modelValue"
        ref="inputRef"
        v-bind="attrs"
        :class="cn(
      'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground border-input flex h-10 w-full min-w-0 rounded-lg border bg-gray-50/80 px-3 py-1 text-base shadow-xs outline-none transition-all duration-200 file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
      'hover:border-gray-300',
      'focus:border-primary focus:bg-white focus:ring-[3px] focus:ring-primary/[0.06] focus:-translate-y-px',
      'aria-invalid:ring-destructive/20 aria-invalid:border-destructive',
      props.class
    )"
    />
</template>

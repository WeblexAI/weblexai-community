<script setup lang="ts">
import { Input } from '@/components/ui/input'
import { InputGroup, InputGroupText } from '@/components/ui/input-group'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { Search } from 'lucide-vue-next'
import { HTMLAttributes, computed, ref, useAttrs } from 'vue'

defineOptions({
    inheritAttrs: false,
})

const props = defineProps<{
    defaultValue?: string | number
    modelValue?: string | number
    class?: HTMLAttributes['class']
    inputClass?: HTMLAttributes['class']
}>()

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
})

const attrs = useAttrs()

const wrapperClass = computed(() => cn(props.class, attrs.class as HTMLAttributes['class']))
const wrapperStyle = computed(() => attrs.style)
const inputAttrs = computed(() => {
    const { class: _class, style: _style, ...rest } = attrs
    return rest
})

const inputRef = ref<HTMLInputElement>()

defineExpose({ inputRef })
</script>

<template>
    <InputGroup
        :style="wrapperStyle"
        :class="cn(
            'bg-white border-gray-200 shadow-none transition-all duration-200 focus-within:border-primary focus-within:bg-white focus-within:ring-[3px] focus-within:ring-primary/[0.06] focus-within:-translate-y-px',
            wrapperClass,
        )"
    >
        <InputGroupText class="pl-3 pr-1 text-gray-400 transition-colors group-focus-within/input-group:text-primary/70">
            <Search class="size-4" />
        </InputGroupText>

        <Input
            ref="inputRef"
            v-model="modelValue"
            v-bind="inputAttrs"
            type="search"
            :class="cn(
                'border-0 bg-transparent px-3 pl-1 shadow-none hover:border-transparent focus:border-0 focus:bg-transparent focus:ring-0 focus:-translate-y-0',
                props.inputClass,
            )"
        />
    </InputGroup>
</template>

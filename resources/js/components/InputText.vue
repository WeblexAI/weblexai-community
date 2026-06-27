<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { computed, useId } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    form?: Record<string, any>;
    model?: string;
    modelValue?: string | number | null;
    label?: string;
    containerClass?: string;
    textarea?: boolean;
    class?: string;
    id?: string;
    required?: boolean;
    placeholder?: string;
    disabled?: boolean;
    type?: string;
};

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    modelValue: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();

const uniqueId = useId();
const inputId = computed(() => props.id || uniqueId);

const proxyValue = computed({
    get() {
        if (props.form && props.model) {
            return props.form[props.model];
        }
        return props.modelValue;
    },
    set(val: string | number | null) {
        if (props.form && props.model) {
            props.form[props.model] = val;
        }
        emit('update:modelValue', val);
    },
});

const errorMessage = computed(() => {
    if (props.form && props.model && props.form.errors) {
        return props.form.errors[props.model];
    }
    return null;
});

const isRequired = computed(() => props.required);
</script>

<template>
    <div :class="cn('grid gap-2', props.containerClass)">
        <Label v-if="label" :for="inputId" class="flex items-center gap-1">
            {{ label }}
            <span v-if="isRequired" class="text-destructive">*</span>
        </Label>

        <div class="relative">
            <Textarea v-if="textarea" :id="inputId" v-model="proxyValue" :class="cn('resize-none', props.class)" :placeholder="placeholder" :disabled="disabled" v-bind="$attrs" />
            <Input v-else :id="inputId" :type="type" v-model="proxyValue" :class="props.class" :placeholder="placeholder" :disabled="disabled" v-bind="$attrs" />
        </div>

        <InputError :message="errorMessage" />
        <slot />
    </div>
</template>

<style scoped></style>

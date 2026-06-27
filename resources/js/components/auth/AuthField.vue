<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    id: string;
    label: string;
    type?: string;
    modelValue: string;
    error?: string;
    placeholder?: string;
    required?: boolean;
    autofocus?: boolean;
    autocomplete?: string;
    readonly?: boolean;
    disabled?: boolean;
}>();

defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const isPassword = computed(() => props.type === 'password');
const showPassword = ref(false);
const resolvedType = computed(() => {
    if (isPassword.value) {
        return showPassword.value ? 'text' : 'password';
    }
    return props.type ?? 'text';
});
</script>

<template>
    <div class="auth-field group">
        <label :for="id" class="mb-2 block font-mono text-[10px] tracking-[0.12em] text-gray-400 uppercase transition-colors duration-200 group-focus-within:text-[#34a85a]">
            {{ label }}
        </label>
        <div class="relative">
            <input
                :id="id"
                :type="resolvedType"
                :value="modelValue"
                @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
                :placeholder="placeholder"
                :required="required"
                :autofocus="autofocus"
                :autocomplete="autocomplete"
                :readonly="readonly"
                :disabled="disabled"
                :class="[
                    'auth-input h-11 w-full rounded-lg text-[14px] text-gray-900 outline-none',
                    'transition-all duration-200 placeholder:text-gray-300',
                    isPassword ? 'px-4 pr-11' : 'px-4',
                    readonly || disabled
                        ? 'cursor-not-allowed border border-gray-200 bg-gray-100 text-gray-400'
                        : 'border border-gray-200 bg-gray-50/80 hover:border-gray-300 focus:-translate-y-px focus:border-[#34a85a] focus:bg-white focus:ring-[3px] focus:ring-[#34a85a]/[0.06]',
                ]"
            />
            <button
                v-if="isPassword"
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 transition-colors hover:text-gray-600"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
            >
                <EyeOff v-if="showPassword" :size="16" />
                <Eye v-else :size="16" />
            </button>
        </div>
        <p v-if="error" class="mt-1.5 text-[12px] text-red-500">{{ error }}</p>
    </div>
</template>

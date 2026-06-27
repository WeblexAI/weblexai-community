<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next';

defineProps<{
    processing?: boolean;
    text: string;
    loadingText?: string;
}>();
</script>

<template>
    <button
        type="submit"
        :disabled="processing"
        class="auth-btn h-11 w-full rounded-lg bg-[#34a85a] text-[13px] font-semibold text-white transition-all duration-200 outline-none hover:-translate-y-px hover:bg-[#2d9550] hover:shadow-[0_4px_14px_rgba(52,168,90,0.25)] focus-visible:ring-[3px] focus-visible:ring-[#34a85a]/20 focus-visible:ring-offset-2 active:translate-y-0 active:shadow-sm disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0 disabled:hover:shadow-none"
    >
        <span class="inline-flex items-center gap-2">
            <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
            {{ processing ? (loadingText ?? 'Processing...') : text }}
        </span>
    </button>
</template>

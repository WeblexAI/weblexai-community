<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import 'vue-sonner/style.css';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-screen flex-col justify-center bg-gray-100 py-12 sm:px-6 lg:px-8">
        <Toaster position="top-right" richColors />
        <div class="mb-8 flex justify-center">
            <img alt="Webspeak AI" class="max-w-[200px]" src="/images/logo-ttb-dark.png" />
        </div>
        <slot />
    </div>
</template>

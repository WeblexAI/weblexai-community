<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="flex flex-col gap-5">
        <div v-for="i in 5" :key="`activity-skeleton-${i}`" class="flex items-start gap-3">
            <Skeleton class="h-6 w-6 rounded-full" />
            <div class="flex w-full flex-col gap-5">
                <Skeleton class="h-6 w-48 rounded" />
                <Skeleton class="h-4 w-32 rounded" />
            </div>
        </div>
        <div class="text-center">
            <Skeleton class="mx-auto h-4 w-28 rounded" />
        </div>
    </div>
</template>

<style scoped></style>

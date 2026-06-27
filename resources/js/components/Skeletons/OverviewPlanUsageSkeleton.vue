<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="flex justify-between">
        <div>
            <Skeleton class="h-4 w-24 rounded" />
            <Skeleton class="mt-2 h-4 w-32 rounded" />
        </div>
        <div>
            <Skeleton class="h-7 w-24 rounded-sm" />
        </div>
    </div>
    <div class="mt-5 space-y-2">
        <Skeleton class="h-4 w-36 rounded" />
        <Skeleton class="h-4 w-40 rounded" />
        <Skeleton class="mt-3 h-2 w-full rounded" />
    </div>
    <div class="mt-5 space-y-2">
        <Skeleton class="h-4 w-40 rounded" />
        <Skeleton class="h-4 w-48 rounded" />
        <Skeleton class="mt-3 h-2 w-full rounded" />
        <Skeleton class="h-3 w-64 rounded" />
    </div>
</template>

<style scoped></style>

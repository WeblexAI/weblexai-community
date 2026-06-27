<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="space-y-6">
        <!-- Stats Grid Skeleton -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div v-for="i in 4" :key="i" class="space-y-2 rounded-xl border bg-card p-6 text-card-foreground shadow">
                <div class="flex items-center justify-between">
                    <Skeleton class="h-4 w-24" />
                    <Skeleton class="h-4 w-4 rounded-full" />
                </div>
                <Skeleton class="h-8 w-16" />
                <Skeleton class="h-3 w-32" />
            </div>
        </div>

        <!-- Config Cards Skeleton -->
        <div class="grid gap-4 md:grid-cols-3">
            <div v-for="i in 3" :key="i" class="space-y-4 rounded-xl border bg-card p-6 text-card-foreground shadow">
                <Skeleton class="h-5 w-32" />
                <div class="space-y-2">
                    <Skeleton class="h-8 w-12" />
                    <Skeleton class="h-4 w-24" />
                </div>
                <Skeleton class="h-4 w-full" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>

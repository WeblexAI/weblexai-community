<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <Card class="col-span-1 mt-7 rounded-sm border border-gray-200 shadow-none">
        <CardHeader class="flex justify-between border-b border-b-gray-300 pb-3">
            <Skeleton class="h-5 w-28 rounded" />
            <Skeleton class="h-4 w-16 rounded" />
        </CardHeader>
        <CardContent class="space-y-5 pt-4 text-sm">
            <div class="space-y-3">
                <Skeleton class="h-4 w-24 rounded" />
                <Skeleton class="h-9 w-9 rounded-full" />
            </div>
            <div class="space-y-3">
                <Skeleton class="h-4 w-16 rounded" />
                <div class="flex space-x-2">
                    <Skeleton class="h-9 w-9 rounded-full" />
                    <Skeleton class="h-9 w-9 rounded-full" />
                    <Skeleton class="h-9 w-9 rounded-full" />
                </div>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped></style>

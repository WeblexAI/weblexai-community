<script setup lang="ts">
import EmptyBox from '@/components/EmptyBox.vue';

defineProps<{
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-2 py-10">
        <EmptyBox />
        <div class="text-faded text-center font-semibold">No results</div>
        <div v-if="description" class="text-faded text-center">{{ description }}</div>
    </div>
</template>

<style scoped></style>

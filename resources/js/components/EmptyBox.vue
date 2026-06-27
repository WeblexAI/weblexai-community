<script setup lang="ts">
import { cn } from '@/lib/utils';
import { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <img :class="cn('w-[50px]', props.class)" src="/images/empty-box.png" />
</template>

<style scoped></style>

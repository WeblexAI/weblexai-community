<script setup lang="ts">
import { cn } from '@/lib/utils';
import { LanguageI } from '@/types';
import { HTMLAttributes } from 'vue';

type Props = {
    language: LanguageI | null;
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();
</script>

<template>
    <span :class="cn('flex items-center gap-2 text-foreground', props.class)">
        <img v-if="language" :src="language.flag_url ?? language.flag?.original_url" class="w-[20px]" alt="" loading="lazy" />
        {{ language?.name ?? '' }}
    </span>
</template>

<style scoped></style>

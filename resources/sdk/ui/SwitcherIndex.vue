<script setup lang="ts">
import { useWindowSize } from '@vueuse/core';
import { computed } from 'vue';
import type { StateI, WeblexAIEnginePublicI } from '../types';
import LanguageSwitcher from './languageSwitcher.vue';
import Loader from './loader.vue';

interface Props {
    state: StateI;
    engine: WeblexAIEnginePublicI;
}

const props = defineProps<Props>();
const switcherConfig = computed(() => props.engine.projectConfig?.switcher_config ?? null);
const { width } = useWindowSize();

const shouldRender = computed(() => {
    const config = switcherConfig.value;

    if (!config || !config.should_show_by_device || !config.preferred_device) {
        return true;
    }

    const isDesktop = width.value >= config.device_pixel_breakpoint;
    return config.preferred_device === 'desktop' ? isDesktop : !isDesktop;
});
</script>

<template>
    <div v-if="shouldRender" class="wlai-switcher-root">
        <Loader v-if="state.loading" />
        <LanguageSwitcher v-if="state.languages.length" :languages="state.languages" :engine="engine" />
    </div>
</template>

<script setup lang="ts">
import { vElementHover, vOnClickOutside } from '@vueuse/components';
import { AnimatePresence, motion } from 'motion-v';
import { computed, nextTick, onMounted, ref } from 'vue';
import '../styles/switcher.css';
import type { LanguageI, ProjectConfigI, WeblexAIEnginePublicI } from '../types';

const props = defineProps<{
    languages: LanguageI[];
    engine: WeblexAIEnginePublicI;
}>();

const open = ref(false);
const dropdownDirection = ref<'top' | 'bottom'>('bottom');
const triggerRef = ref<HTMLElement | null>(null);
const dropdownRef = ref<HTMLElement | null>(null);

const selectedLanguage = computed(() => props.engine.state.selectedLang as LanguageI | null);
const projectConfig = computed(() => props.engine.projectConfig);
const switcherConfig = computed(() => projectConfig.value?.switcher_config as ProjectConfigI['switcher_config']);
const availableLanguages = computed(() => {
    return props.languages.filter((language) => language.iso_2 !== selectedLanguage.value?.iso_2);
});

const langNameFontSize = computed(() => Math.ceil((switcherConfig.value.size * 17) / 50));
const flagWidth = computed(() => Math.ceil((switcherConfig.value.size * 20) / 50));
const flagHeight = computed(() => Math.ceil((flagWidth.value * 14) / 20));

const flagStyles = computed(() => ({
    width: `${flagWidth.value}px`,
    height: `${flagHeight.value}px`,
}));

const switcherStyles = computed(() => {
    if (switcherConfig.value.target_parent_selector) {
        return {
            fontSize: `${langNameFontSize.value}px`,
            position: 'relative' as const,
        };
    }

    return {
        fontSize: `${langNameFontSize.value}px`,
        position: 'fixed' as const,
        bottom: '0',
        right: '20px',
    };
});

async function toggleDropdown() {
    if (switcherConfig.value.should_open_on_hover) {
        return;
    }

    open.value = !open.value;

    if (open.value) {
        await nextTick();
        decideDropdownDirection();
    }
}

function handleOutsideClick() {
    if (switcherConfig.value.should_close_on_outside_click) {
        open.value = false;
    }
}

async function handleHover(state: boolean) {
    if (!switcherConfig.value.should_open_on_hover) {
        return;
    }

    open.value = state;

    if (state) {
        await nextTick();
        decideDropdownDirection();
    }
}

async function select(language: LanguageI) {
    open.value = false;
    await props.engine.translateTo(language.iso_2);
}

function decideDropdownDirection() {
    const trigger = triggerRef.value;
    if (!trigger) {
        return;
    }

    const dropdownHeight = dropdownRef.value?.scrollHeight || availableLanguages.value.length * 44;
    const rect = trigger.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    dropdownDirection.value = spaceBelow >= dropdownHeight || spaceBelow >= spaceAbove ? 'bottom' : 'top';
}

onMounted(() => {
    decideDropdownDirection();
});
</script>

<template>
    <div class="wlai-language-switcher" :style="switcherStyles" v-on-click-outside="handleOutsideClick" v-element-hover="handleHover">
        <div ref="triggerRef" class="wlai-selected-container" :class="{ 'wlai-selected-container--top': dropdownDirection === 'top' }" @click="toggleDropdown">
            <div v-if="!projectConfig?.hide_water_mark" class="wlai-watermark">weblexai</div>

            <div class="wlai-selected">
                <img v-if="selectedLanguage?.flag && switcherConfig.should_display_flag" :src="selectedLanguage.flag" class="wlai-flag" :alt="selectedLanguage.name" :style="flagStyles" />
                <span v-if="switcherConfig.should_display_name">
                    <span v-if="switcherConfig.should_display_full_name">{{ selectedLanguage?.name }}</span>
                    <span v-else>{{ selectedLanguage?.iso_2.toUpperCase() }}</span>
                </span>
            </div>
        </div>

        <AnimatePresence>
            <motion.ul
                v-show="open"
                ref="dropdownRef"
                class="wlai-dropdown"
                :class="{ 'wlai-dropdown--top': dropdownDirection === 'top' }"
                :initial="{ opacity: 0, scale: 0.96 }"
                :animate="{ opacity: 1, scale: 1 }"
                :exit="{ opacity: 0, scale: 0.96 }"
            >
                <li v-for="language in availableLanguages" :key="language.iso_2" @click="select(language)">
                    <img v-if="language.flag && switcherConfig.should_display_flag" :src="language.flag" class="wlai-flag" :alt="language.name" :style="flagStyles" />
                    <span v-if="switcherConfig.should_display_name">
                        <span v-if="switcherConfig.should_display_full_name">{{ language.name }}</span>
                        <span v-else>{{ language.iso_2.toUpperCase() }}</span>
                    </span>
                </li>
            </motion.ul>
        </AnimatePresence>
    </div>
</template>

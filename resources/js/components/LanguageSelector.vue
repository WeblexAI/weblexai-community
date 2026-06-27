<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { IconInput } from '@/components/ui/icon-input';
import { Label } from '@/components/ui/label';

import LanguageWithFlag from '@/components/language/LanguageWithFlag.vue';
import { LanguageI } from '@/types';
import { computed, onMounted, onUnmounted, ref, useAttrs, watch } from 'vue';

type Props = {
    form: Record<string, any>;
    model: string;
    label?: string;
    languages: LanguageI[];
    searchable?: boolean;
};

const props = defineProps<Props>();
const attrs = useAttrs();
const id = Math.random().toString(36).substring(2, 10);

const searchQuery = ref('');
const open = ref(false);
const dropdownRef = ref<HTMLElement>();
const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.languages;
    return props.languages.filter((language) => language.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

function handleClickOutside(event: MouseEvent) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

watch(open, (val) => {
    if (!val) {
        searchQuery.value = '';
    }
});

function selectOption(value: number) {
    if (!props.form || !props.model) return;
    props.form[props.model] = value;
    open.value = false;
}
</script>

<template>
    <div class="grid gap-2">
        <Label :for="id">
            {{ props.label }}
            <span class="text-destructive" v-show="props.label && attrs.hasOwnProperty('required')">*</span>
        </Label>

        <div v-if="form && model">
            <div class="relative" ref="dropdownRef">
                <div
                    class="flex h-11 w-full cursor-pointer items-center justify-between rounded-lg border border-input bg-gray-50/80 px-3 py-2 text-sm transition-all duration-200 outline-none hover:border-gray-300"
                    @click="!attrs.disabled && (open = !open)"
                    :class="{ 'cursor-not-allowed opacity-100': attrs.disabled }"
                >
                    <LanguageWithFlag v-if="form[model] && languages.length" :language="languages.find((language) => language.id === form[model]) ?? null" />
                    <span v-else class="text-muted-foreground"> Select a language </span>

                    <svg class="ml-2 h-4 w-4 text-muted-foreground transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>

                <div v-if="open" class="absolute z-50 mt-1 max-h-60 w-full overflow-hidden rounded-md border border-border bg-popover shadow-md">
                    <div v-if="props.searchable" class="border-b border-border p-2">
                        <IconInput v-model="searchQuery" @click.stop placeholder="Search...">
                            <template #icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </template>
                        </IconInput>
                    </div>

                    <div class="max-h-48 overflow-y-auto pb-5">
                        <div
                            v-for="language in filteredOptions"
                            :key="language.id"
                            @click="selectOption(language.id)"
                            class="group flex cursor-pointer items-center gap-2 px-3 py-2 transition-colors hover:bg-accent"
                        >
                            <LanguageWithFlag :language="language" />
                            <div class="ms-auto flex items-center">
                                <div v-if="props.form[props.model] === language.id" class="flex h-4 w-4 items-center justify-center rounded-full bg-primary">
                                    <svg class="h-2.5 w-2.5 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div v-else class="h-4 w-4 rounded-full border-2 border-border transition-colors group-hover:border-primary"></div>
                            </div>
                        </div>

                        <div v-if="props.searchable && filteredOptions.length === 0" class="p-3 text-center text-muted-foreground">No results found</div>
                    </div>
                </div>
            </div>
            <slot />
            <InputError :message="form.errors[model]" class="mt-2" />
        </div>
    </div>
</template>

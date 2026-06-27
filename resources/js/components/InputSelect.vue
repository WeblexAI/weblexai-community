<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { IconInput } from '@/components/ui/icon-input';
import { Label } from '@/components/ui/label';
import { InputSelectOption } from '@/types';
import { computed, onMounted, onUnmounted, ref, useAttrs, watch } from 'vue';

type Props = {
    form: Record<string, any>;
    model: string;
    label?: string;
    options: InputSelectOption[];
    searchable?: boolean;
    taggable?: boolean;
    optionsOnly?: boolean;
    disabled?: boolean;
};

const props = defineProps<Props>();
const attrs = useAttrs();
const id = Math.random().toString(36).substring(2, 10);

const searchQuery = ref('');
const open = ref(false);
const dropdownRef = ref<HTMLElement>();
const filteredOptions = computed(() => {
    if (!props.searchable || !searchQuery.value) return props.options;
    return props.options.filter((o) => o.label.toLowerCase().includes(searchQuery.value.toLowerCase()));
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

function toggleTag(value: string | number) {
    const modelValue = props.form[props.model];
    const index = modelValue.indexOf(value);

    if (index === -1) {
        props.form[props.model].push(value);
    } else {
        props.form[props.model].splice(index, 1);
    }
}

function addNewTag() {
    if (props.optionsOnly) return;
    const newValue = searchQuery.value.trim();
    if (newValue && !props.form[props.model].includes(newValue)) {
        props.form[props.model].push(newValue);
        searchQuery.value = '';
    }
}

function removeTag(value: string | number) {
    const index = props.form[props.model].indexOf(value);
    if (index !== -1) props.form[props.model].splice(index, 1);
}

function selectOption(value: string | number) {
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

        <div v-if="props.form && props.model">
            <template v-if="props.taggable">
                <div class="relative" ref="dropdownRef">
                    <div
                        class="flex min-h-10 w-full cursor-pointer flex-wrap items-center justify-between gap-2 rounded-md border border-input px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        @click="open = !open"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                v-for="tag in props.form[props.model]"
                                :key="tag"
                                class="inline-flex items-center gap-1 rounded-md bg-primary px-2 py-1 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                            >
                                {{ props.options.find((opt) => opt.value === tag)?.label || tag }}
                                <button @click.stop="removeTag(tag)" class="ml-1 rounded-full p-0.5 transition-colors hover:bg-primary-foreground/20" aria-label="Remove tag">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </span>
                        </div>

                        <span v-if="!props.form[props.model]?.length" class="text-muted-foreground select-none">
                            {{ optionsOnly ? 'Select option' : 'Select or add options...' }}
                        </span>

                        <div class="ml-auto">
                            <svg class="h-4 w-4 text-muted-foreground transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>

                    <div v-if="open" class="absolute z-50 mt-1 max-h-60 w-full overflow-hidden rounded-md border border-border bg-popover shadow-md">
                        <div class="border-b border-border p-2" v-if="searchable">
                            <IconInput v-model="searchQuery" @keyup.enter="addNewTag" :placeholder="optionsOnly ? `Search..` : `Search or type to add...`">
                                <template #icon>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </template>
                            </IconInput>
                        </div>

                        <div class="max-h-48 overflow-y-auto">
                            <div
                                v-for="option in filteredOptions"
                                :key="option.value"
                                @click="toggleTag(option.value)"
                                class="group flex cursor-pointer items-center justify-between px-3 py-2 transition-colors hover:bg-accent"
                            >
                                <span class="text-foreground">{{ option.label }}</span>
                                <div class="flex items-center">
                                    <div
                                        v-if="Array.isArray(props.form[props.model]) && props.form[props.model].includes(option.value)"
                                        class="flex h-4 w-4 items-center justify-center rounded-full bg-primary"
                                    >
                                        <svg class="h-2.5 w-2.5 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div v-else class="h-4 w-4 rounded-full border-2 border-border transition-colors group-hover:border-primary"></div>
                                </div>
                            </div>

                            <div v-if="props.searchable && filteredOptions.length === 0 && searchQuery.trim()" class="p-3 text-center">
                                <div class="mb-2 text-muted-foreground">No results found</div>
                                <button
                                    @click="addNewTag"
                                    class="inline-flex items-center gap-2 rounded-md bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary transition-colors hover:bg-primary/20"
                                    v-if="!optionsOnly"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add "{{ searchQuery.trim() }}"
                                </button>
                            </div>

                            <div v-else-if="props.searchable && filteredOptions.length === 0 && !searchQuery.trim()" class="p-3 text-center text-muted-foreground">
                                Start typing to search <span v-if="!optionsOnly">or add new options</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="relative" ref="dropdownRef">
                    <div
                        class="flex h-10 w-full cursor-pointer items-center justify-between rounded-md border border-input px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        @click="!attrs.disabled && (open = !open)"
                        :class="{ 'cursor-not-allowed opacity-100': attrs.disabled }"
                    >
                        <span v-if="props.form[props.model] && props.options.length" class="text-foreground">
                            {{ props.options.find((opt) => opt.value === props.form[props.model])?.label || props.form[props.model] }}
                        </span>
                        <span v-else class="text-muted-foreground">
                            {{ attrs.placeholder || 'Select an option' }}
                        </span>

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

                        <div class="max-h-48 overflow-y-auto">
                            <div
                                v-for="option in filteredOptions"
                                :key="option.value"
                                @click="selectOption(option.value)"
                                class="group flex cursor-pointer items-center justify-between px-3 py-2 transition-colors hover:bg-accent"
                            >
                                <span class="text-foreground">{{ option.label }}</span>
                                <div class="flex items-center">
                                    <div v-if="props.form[props.model] === option.value" class="flex h-4 w-4 items-center justify-center rounded-full bg-primary">
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
            </template>
            <InputError :message="props.form.errors[props.model]" />
        </div>
    </div>
</template>

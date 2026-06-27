<script setup lang="ts">
import ConfirmAction from '@/components/ConfirmAction.vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import useAuthorization from '@/composables/useAuthorization';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { LanguageI } from '@/types';
import { router } from '@inertiajs/vue3';
import { Eye, EyeOff, Globe, LoaderCircle, Lock, Trash2, Unlock } from 'lucide-vue-next';
import { ref } from 'vue';

const { canManageSettings } = useAuthorization();

type Props = {
    language: LanguageI;
};

const props = defineProps<Props>();
const detachingLanguage = ref(false);
const should_display_automatics = ref(props.language.pivot?.should_display_automatics ?? true);
const is_public = ref(props.language.pivot?.is_public ?? true);
const togglingPublicity = ref(false);
const togglingAutomatics = ref(false);
const disablingLanguage = ref(false);

async function removeLanguage() {
    router.delete(routeWithProject('projects.languages.delete', { language: props.language.id }), {
        onSuccess: (res) => {
            toastResponse(res);
        },
        onStart: () => (detachingLanguage.value = true),
        onFinish: () => (detachingLanguage.value = false),
    });
}

function disableLanguage() {
    router.post(
        routeWithProject('projects.languages.disable', { language: props.language.id }),
        {},
        {
            onSuccess: (res) => {
                toastResponse(res);
            },
            onStart: () => (disablingLanguage.value = true),
            onFinish: () => (disablingLanguage.value = false),
        },
    );
}

function toggleIsPublic(newValue: boolean) {
    router.post(
        routeWithProject('projects.languages.toggle-publicity', { language: props.language.id }),
        {
            is_public: newValue,
        },
        {
            onSuccess: (res) => {
                toastResponse(res);
            },
            onError: () => {
                is_public.value = !newValue;
            },
            onStart: () => (togglingPublicity.value = true),
            onFinish: () => (togglingPublicity.value = false),
        },
    );
}

function toggleAutomatics(newValue: boolean) {
    router.post(
        routeWithProject('projects.languages.toggle-automatics', { language: props.language.id }),
        {
            should_display_automatics: newValue,
        },
        {
            onSuccess: (res) => {
                toastResponse(res);
            },
            onError: () => {
                should_display_automatics.value = !newValue;
            },
            onStart: () => (togglingAutomatics.value = true),
            onFinish: () => (togglingAutomatics.value = false),
        },
    );
}
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <slot />
        </PopoverTrigger>
        <PopoverContent class="w-[320px] p-0" align="end">
            <div class="border-b border-gray-100 px-4 py-3">
                <div class="flex items-center gap-2">
                    <Globe class="h-4 w-4 text-primary" />
                    <h4 class="text-sm font-semibold text-gray-900">Language Settings</h4>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">Configure {{ language.name }} options</p>
            </div>

            <div class="py-2">
                <div class="px-4 py-3 transition-colors hover:bg-gray-50">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <div class="rounded-md bg-blue-50 p-1.5 text-blue-600">
                                <component :is="is_public ? Unlock : Lock" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ is_public ? 'Public' : 'Private' }}
                                    </span>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button class="text-gray-400 transition-colors hover:text-gray-600">
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="h-3.5 w-3.5"
                                                >
                                                    <circle cx="12" cy="12" r="10" />
                                                    <path d="M12 16v-4" />
                                                    <path d="M12 8h.01" />
                                                </svg>
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent class="max-w-[250px]">
                                            <p class="text-xs">Private languages require <strong>?wsai-prv=1</strong> in the URL to be accessed.</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ is_public ? 'Visible to all visitors' : 'Hidden from visitors' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <LoaderCircle v-if="togglingPublicity" class="h-4 w-4 animate-spin text-primary" />
                            <Switch
                                v-else
                                v-model:model-value="is_public"
                                @update:model-value="(val) => toggleIsPublic(val as boolean)"
                                :disabled="togglingAutomatics || togglingPublicity || !canManageSettings"
                            />
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3 transition-colors hover:bg-gray-50">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <div class="rounded-md bg-green-50 p-1.5 text-green-600">
                                <component :is="should_display_automatics ? Eye : EyeOff" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900">Auto Translations</span>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button class="text-gray-400 transition-colors hover:text-gray-600">
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="h-3.5 w-3.5"
                                                >
                                                    <circle cx="12" cy="12" r="10" />
                                                    <path d="M12 16v-4" />
                                                    <path d="M12 8h.01" />
                                                </svg>
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent class="max-w-[250px]">
                                            <p class="text-xs">When disabled, only manual translations are shown. Untranslated text displays in the original language.</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ should_display_automatics ? 'Showing AI translations' : 'Manual only' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <LoaderCircle v-if="togglingAutomatics" class="h-4 w-4 animate-spin text-primary" />
                            <Switch
                                v-else
                                v-model:model-value="should_display_automatics"
                                @update:model-value="(val) => toggleAutomatics(val as boolean)"
                                :disabled="togglingAutomatics || togglingPublicity || !canManageSettings"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-gray-100" />

            <div v-if="!language.pivot?.is_disabled && canManageSettings" class="p-2">
                <ConfirmAction
                    :action="disableLanguage"
                    variant="destructive"
                    title="Disable Language"
                    description="Are you sure you want to disable this language? Translations will no longer be served, but your manual translations will be preserved."
                    :loading="disablingLanguage"
                >
                    <button class="group flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-orange-600 transition-colors hover:bg-orange-50" :disabled="disablingLanguage">
                        <div class="rounded bg-orange-100 p-1.5 text-orange-600 transition-colors group-hover:bg-orange-200">
                            <EyeOff class="h-3.5 w-3.5" />
                        </div>
                        <span>Disable Language</span>
                        <LoaderCircle v-if="disablingLanguage" class="ml-auto h-4 w-4 animate-spin text-orange-600" />
                    </button>
                </ConfirmAction>
            </div>

            <div class="h-px bg-gray-100" />

            <div v-if="canManageSettings" class="p-2">
                <ConfirmAction
                    :action="removeLanguage"
                    variant="destructive"
                    title="Remove Language"
                    description="Are you sure you want to remove this language? All associated translations will be permanently deleted."
                    :loading="detachingLanguage"
                >
                    <button class="group flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-50" :disabled="detachingLanguage">
                        <div class="rounded bg-red-100 p-1 text-red-600 transition-colors group-hover:bg-red-200">
                            <Trash2 class="h-3.5 w-3.5" />
                        </div>
                        <span>Remove Language</span>
                        <LoaderCircle v-if="detachingLanguage" class="ml-auto h-4 w-4 animate-spin" />
                    </button>
                </ConfirmAction>
            </div>
        </PopoverContent>
    </Popover>
</template>

<style scoped></style>

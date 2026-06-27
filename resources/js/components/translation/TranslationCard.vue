<script setup lang="ts">
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import useAuthorization from '@/composables/useAuthorization';
import { TranslationQualityE } from '@/enums';
import { randomString, routeWithProject, toastResponse } from '@/lib/helpers';
import { TranslationI } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Save, SpellCheck } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const { canManageContent } = useAuthorization();

type Props = {
    translation: TranslationI;
};

const props = defineProps<Props>();

const translatedForm = useForm({
    translation_id: props.translation.id,
    translated: props.translation.translated,
});
const qualityForm = useForm({
    translation_id: props.translation.id,
    is_reviewed: props.translation.is_reviewed,
});
const isOnForm = useForm({
    translation_id: props.translation.id,
    is_on: props.translation.is_on,
});

const autoTextarea = ref<HTMLTextAreaElement | null>(null);
const inputId = randomString();

const autoResize = () => {
    const textarea = autoTextarea.value;
    if (textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
};

onMounted(() => {
    if (autoTextarea.value) autoResize();
});

function saveTranslation() {
    if (!translatedForm.processing) {
        translatedForm.put(routeWithProject('projects.translations.translated'), {
            onSuccess: (res) => toastResponse(res),
        });
    }
}

function toggleReview() {
    qualityForm.is_reviewed = !props.translation.is_reviewed;
    qualityForm.put(routeWithProject('projects.translations.review'), {
        onSuccess: (res) => toastResponse(res),
    });
}

function toggleIsOn(value: boolean) {
    isOnForm.is_on = value;
    isOnForm.put(routeWithProject('projects.translations.visibility'), {
        onSuccess: (res) => toastResponse(res),
    });
}
</script>

<template>
    <TooltipProvider>
        <div class="rounded-xs bg-white p-3">
            <div class="mb-2 text-sm">
                <label :for="inputId">{{ translation.text }}</label>
            </div>
            <div>
                <textarea
                    class="w-full resize-none overflow-hidden border border-secondary p-2 text-sm focus:border-accent focus:outline-0 focus:outline-accent disabled:bg-gray-100"
                    v-model="translatedForm.translated"
                    @input="autoResize"
                    ref="autoTextarea"
                    :disabled="translatedForm.processing || !canManageContent"
                    :id="inputId"
                />
            </div>
            <div class="mt-2 flex items-center gap-4">
                <div class="text-xs italic" :class="{ 'text-primary': translation.quality === TranslationQualityE.MANUAL }">
                    {{ translation.quality === TranslationQualityE.MANUAL ? 'manual' : 'automatic' }}
                </div>
                <div class="ms-auto flex items-center gap-4">
                    <Tooltip v-if="canManageContent">
                        <TooltipTrigger>
                            <Save @click="saveTranslation" :size="20" class="cursor-pointer" :class="{ 'cursor-progress text-gray-400': translatedForm.processing }" />
                        </TooltipTrigger>
                        <TooltipContent>Save Changes</TooltipContent>
                    </Tooltip>
                    <Tooltip v-if="canManageContent">
                        <TooltipTrigger>
                            <SpellCheck
                                @click="toggleReview"
                                :size="20"
                                class="cursor-pointer"
                                :class="{
                                    'cursor-progress text-gray-400': qualityForm.processing,
                                    'text-primary': qualityForm.is_reviewed,
                                }"
                            />
                        </TooltipTrigger>
                        <TooltipContent>
                            {{ translation.is_reviewed ? 'Mark as pending review' : 'Mark as reviewed' }}
                        </TooltipContent>
                    </Tooltip>
                    <Tooltip v-if="canManageContent">
                        <TooltipTrigger>
                            <Switch
                                :disabled="isOnForm.processing || !canManageContent"
                                :model-value="isOnForm.is_on"
                                @update:model-value="toggleIsOn"
                                id="airplane-mode"
                                class="cursor-pointer disabled:cursor-progress data-[state=unchecked]:border-red-500 data-[state=unchecked]:bg-transparent"
                                thumb-class="data-[state=unchecked]:bg-red-500"
                            />
                        </TooltipTrigger>
                        <TooltipContent>
                            {{ translation.is_on ? 'Turn off translation' : 'Turn on translation' }}
                        </TooltipContent>
                    </Tooltip>
                </div>
            </div>
        </div>
    </TooltipProvider>
</template>

<style scoped></style>

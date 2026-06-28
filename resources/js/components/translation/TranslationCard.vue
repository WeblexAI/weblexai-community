<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import useAuthorization from '@/composables/useAuthorization';
import { TranslationQualityE } from '@/enums';
import { randomString, routeWithProject, toastResponse } from '@/lib/helpers';
import { TranslationI } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, Save, SpellCheck } from 'lucide-vue-next';
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
    qualityForm.is_reviewed = !qualityForm.is_reviewed;
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
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="space-y-1">
                    <div class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Source</div>
                    <label :for="inputId" class="block text-sm leading-6 text-slate-900">{{ translation.text }}</label>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <Badge :variant="translation.quality === TranslationQualityE.MANUAL ? 'default' : 'outline'">
                        {{ translation.quality === TranslationQualityE.MANUAL ? 'Manual edit' : 'Automatic' }}
                    </Badge>
                    <Badge :variant="qualityForm.is_reviewed ? 'default' : 'secondary'">
                        {{ qualityForm.is_reviewed ? 'Reviewed' : 'Pending review' }}
                    </Badge>
                    <Badge :variant="isOnForm.is_on ? 'outline' : 'destructive'">
                        {{ isOnForm.is_on ? 'Visible' : 'Hidden' }}
                    </Badge>
                </div>
            </div>
            <div class="space-y-2">
                <div class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Translation</div>
                <textarea
                    class="w-full resize-none overflow-hidden rounded-lg border border-slate-200 bg-slate-50/70 p-3 text-sm leading-6 text-slate-900 transition focus:border-primary focus:bg-white focus:ring-[3px] focus:ring-primary/10 focus:outline-none disabled:bg-gray-100"
                    v-model="translatedForm.translated"
                    @input="autoResize"
                    ref="autoTextarea"
                    :disabled="translatedForm.processing || !canManageContent"
                    :id="inputId"
                />
            </div>
            <div class="mt-3 flex flex-col gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-muted-foreground">Save manual edits before marking the translation reviewed.</p>
                <div class="flex items-center gap-2">
                    <Tooltip v-if="canManageContent">
                        <TooltipTrigger as-child>
                            <Button variant="outline" size="sm" :processing="translatedForm.processing" @click="saveTranslation">
                                <Save class="mr-2 h-4 w-4" />
                                Save
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Save manual translation</TooltipContent>
                    </Tooltip>
                    <Tooltip v-if="canManageContent">
                        <TooltipTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="toggleReview"
                                :class="{
                                    'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-50': qualityForm.is_reviewed,
                                }"
                                :processing="qualityForm.processing"
                            >
                                <CheckCircle2 v-if="qualityForm.is_reviewed" class="mr-2 h-4 w-4" />
                                <SpellCheck v-else class="mr-2 h-4 w-4" />
                                {{ qualityForm.is_reviewed ? 'Reviewed' : 'Review' }}
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            {{ qualityForm.is_reviewed ? 'Mark as pending review' : 'Mark as reviewed' }}
                        </TooltipContent>
                    </Tooltip>
                    <Tooltip v-if="canManageContent">
                        <TooltipTrigger as-child>
                            <Switch
                                :disabled="isOnForm.processing || !canManageContent"
                                :model-value="isOnForm.is_on"
                                @update:model-value="toggleIsOn"
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

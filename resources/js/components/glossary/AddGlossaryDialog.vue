<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import InputSelect from '@/components/InputSelect.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { GlossaryRuleE } from '@/enums';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { cn } from '@/lib/utils';
import { InputSelectOption, LanguageI } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { ArrowRightLeft, Ban, BookA, Globe, Save, Type, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Props = {
    languages: LanguageI[];
};

const props = defineProps<Props>();
const open = ref(false);

const languagesOptions = computed<InputSelectOption[]>(() =>
    props.languages.map((language) => ({
        label: language.name,
        value: language.id,
    })),
);

const form = useForm({
    text: '',
    translated: '',
    is_case_sensitive: false,
    rule: GlossaryRuleE.NEVER_TRANSLATED, // Default to never translate
    languages: [],
});

function submitForm() {
    if (!props.languages.length) return;

    // If rule is never translate, clear the translated text
    if (form.rule === GlossaryRuleE.NEVER_TRANSLATED) {
        form.translated = '';
    }

    form.post(routeWithProject('projects.glossaries.create'), {
        preserveScroll: true,
        onSuccess: (res) => {
            toastResponse(res, () => {
                form.reset();
                open.value = false;
            });
        },
    });
}

function selectRule(rule: GlossaryRuleE) {
    form.rule = rule;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-[600px]">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 p-2">
                        <BookA class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle class="text-xl">Add Glossary Rule</DialogTitle>
                        <DialogDescription class="mt-1"> Define how specific terms should be handled during translation. </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="submitForm" class="mt-2 space-y-6">
                <!-- Rule Selection -->
                <div class="space-y-3">
                    <Label class="text-sm font-medium text-gray-700">Rule Type</Label>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Never Translate Option -->
                        <div
                            @click="selectRule(GlossaryRuleE.NEVER_TRANSLATED)"
                            :class="
                                cn(
                                    'relative flex cursor-pointer flex-col gap-2 rounded-xl border-2 p-4 transition-all duration-200 hover:border-primary/50 hover:bg-primary/5',
                                    form.rule === GlossaryRuleE.NEVER_TRANSLATED ? 'border-primary bg-primary/5 shadow-sm' : 'border-gray-100 bg-white',
                                )
                            "
                        >
                            <div class="flex items-center justify-between">
                                <div class="rounded-full bg-orange-100 p-2 text-orange-600">
                                    <Ban class="h-5 w-5" />
                                </div>
                                <div v-if="form.rule === GlossaryRuleE.NEVER_TRANSLATED" class="text-primary">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white">
                                        <X class="h-3 w-3" />
                                        <!-- Using X as checkmark for 'never' conceptually or just check -->
                                        <!-- Actually let's use a checkmark for selection state -->
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="h-3 w-3"
                                        >
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Never Translate</h4>
                                <p class="mt-1 text-xs text-gray-500">Keep the term exactly as is in all languages.</p>
                            </div>
                        </div>

                        <!-- Always Translate Option -->
                        <div
                            @click="selectRule(GlossaryRuleE.ALWAYS_TRANSLATED)"
                            :class="
                                cn(
                                    'relative flex cursor-pointer flex-col gap-2 rounded-xl border-2 p-4 transition-all duration-200 hover:border-primary/50 hover:bg-primary/5',
                                    form.rule === GlossaryRuleE.ALWAYS_TRANSLATED ? 'border-primary bg-primary/5 shadow-sm' : 'border-gray-100 bg-white',
                                )
                            "
                        >
                            <div class="flex items-center justify-between">
                                <div class="rounded-full bg-blue-100 p-2 text-blue-600">
                                    <ArrowRightLeft class="h-5 w-5" />
                                </div>
                                <div v-if="form.rule === GlossaryRuleE.ALWAYS_TRANSLATED" class="text-primary">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="h-3 w-3"
                                        >
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Always Translate</h4>
                                <p class="mt-1 text-xs text-gray-500">Replace the term with a specific translation.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Term Inputs -->
                <div class="space-y-4 rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                    <div class="grid gap-4" :class="form.rule === GlossaryRuleE.ALWAYS_TRANSLATED ? 'sm:grid-cols-2' : ''">
                        <div class="space-y-2">
                            <Label for="text" class="text-sm font-medium"> Original Term <span class="text-destructive">*</span> </Label>
                            <Input id="text" v-model="form.text" placeholder="e.g. BrandName" :disabled="!languages.length || form.processing" class="bg-white" />
                            <InputError :message="form.errors.text" />
                        </div>

                        <div v-if="form.rule === GlossaryRuleE.ALWAYS_TRANSLATED" class="animate-in space-y-2 duration-300 fade-in slide-in-from-left-2">
                            <Label for="translated" class="text-sm font-medium"> Translate To <span class="text-destructive">*</span> </Label>
                            <Input id="translated" v-model="form.translated" placeholder="e.g. TranslatedBrand" :disabled="!languages.length || form.processing" class="bg-white" />
                            <InputError :message="form.errors.translated" />
                        </div>
                    </div>

                    <!-- Case Sensitivity -->
                    <div class="flex items-center space-x-2 pt-2">
                        <Checkbox id="case_sensitive" v-model:checked="form.is_case_sensitive" :disabled="!languages.length || form.processing" />
                        <Label for="case_sensitive" class="flex items-center gap-2 text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                            <Type class="h-4 w-4 text-gray-500" />
                            Case sensitive matching
                        </Label>
                    </div>
                </div>

                <!-- Languages -->
                <div class="space-y-2">
                    <Label class="flex items-center gap-2 text-sm font-medium">
                        <Globe class="h-4 w-4 text-gray-500" />
                        Target Languages
                    </Label>
                    <div v-if="languages.length">
                        <InputSelect :options="languagesOptions" :form="form" taggable options-only model="languages" placeholder="Select languages (leave empty for all)" />
                        <p class="mt-1.5 text-xs text-muted-foreground">Leave empty to apply this rule to <strong>all languages</strong> in the project.</p>
                    </div>
                    <div v-else class="rounded-md border border-yellow-100 bg-yellow-50 p-3 text-sm text-yellow-800">⚠️ You need to add languages to your project before creating glossary rules.</div>
                    <InputError :message="form.errors.languages" />
                </div>

                <!-- Warning Note -->
                <div class="flex items-start gap-2 rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs text-blue-800">
                    <span class="text-base text-blue-500">ℹ️</span>
                    <span class="mt-0.5"
                        >Existing translations matching this rule will be removed and automatically regenerated during the next live page translation, triggered by a visit to the page.</span
                    >
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 pt-2">
                    <Button type="button" variant="outline" @click="open = false" :disabled="form.processing"> Cancel </Button>
                    <Button type="submit" :disabled="!languages.length || form.processing" class="min-w-[120px] bg-primary hover:bg-primary/90">
                        <Save class="mr-2 h-4 w-4" />
                        Save Rule
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>

<script setup lang="ts">
import ConfirmAction from '@/components/ConfirmAction.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import useProject from '@/composables/useProject';
import { TranslationAudienceE, TranslationToneE } from '@/enums';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertCircle, BookOpenCheck, BrainCircuit, CheckCircle2, MessageSquare, Save, Sparkles, Users, Zap } from 'lucide-vue-next';

const project = useProject();
const credential = project.provider_credential;
const isLlm = credential?.provider_type === 'LLM';

const form = useForm({
    website_description: project.website_description ?? '',
    translation_tone: project.translation_tone ?? TranslationToneE.INFORMAL,
    translation_audience: project.translation_audience ?? TranslationAudienceE.GENERAL,
});

function submitForm() {
    form.put(routeWithProject('projects.translation-model.update'), {
        onSuccess: (response) => toastResponse(response),
    });
}
</script>

<template>
    <Head title="Translation Provider" />
    <ProjectLayout page-title="Translation Provider">
        <div class="mx-auto max-w-5xl space-y-8">
            <Alert v-if="!credential" class="border-amber-300 bg-amber-50">
                <AlertCircle class="h-5 w-5 text-amber-600" />
                <AlertTitle>No provider assigned</AlertTitle>
                <AlertDescription>Automatic translations are disabled until an administrator assigns an active provider credential to this project.</AlertDescription>
            </Alert>

            <Card v-else class="overflow-hidden border-slate-200 shadow-sm">
                <CardHeader class="border-b bg-white">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="rounded-xl p-3" :class="isLlm ? 'bg-slate-950 text-emerald-300' : 'bg-emerald-50 text-emerald-700'">
                                <BrainCircuit v-if="isLlm" class="h-6 w-6" />
                                <Zap v-else class="h-6 w-6" />
                            </div>
                            <div>
                                <CardTitle>{{ credential.name }}</CardTitle>
                                <CardDescription class="mt-1">{{ credential.provider_label }} · {{ credential.provider_type }}</CardDescription>
                            </div>
                        </div>
                        <Badge :variant="isLlm ? 'default' : 'outline'">{{ isLlm ? 'Context aware' : 'Direct translation' }}</Badge>
                    </div>
                </CardHeader>
                <CardContent class="grid gap-4 pt-6 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <CheckCircle2 class="h-5 w-5 text-emerald-600" />
                        <div class="mt-3 font-semibold">Provider is active</div>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">Projects use the selected credential for automatic translation requests.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <BookOpenCheck class="h-5 w-5 text-emerald-600" />
                        <div class="mt-3 font-semibold">Glossary rules apply</div>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">Brand terms and protected phrases are applied before provider calls.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <component :is="isLlm ? BrainCircuit : Zap" class="h-5 w-5 text-emerald-600" />
                        <div class="mt-3 font-semibold">{{ isLlm ? 'LLM context enabled' : 'NMT context skipped' }}</div>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            {{ isLlm ? 'Tone, audience, and website context are sent with translation batches.' : 'This provider does not use tone, audience, or website context.' }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="credential && isLlm" class="border-slate-200 bg-white shadow-sm">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-slate-950">
                        <Sparkles class="h-5 w-5 text-emerald-600" />
                        Translation quality controls
                    </CardTitle>
                    <CardDescription>Keep terminology, tone, and audience consistent across automatically generated translations.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label for="description">Website context</Label>
                            <span class="text-xs text-muted-foreground">{{ form.website_description.length }}/250</span>
                        </div>
                        <Textarea
                            id="description"
                            v-model="form.website_description"
                            maxlength="250"
                            class="min-h-28 bg-white"
                            placeholder="Example: WeblexAI is a self-hosted translation platform for technical teams. Keep product names unchanged and use a direct, professional tone."
                        />
                        <InputError :message="form.errors.website_description" />
                        <p class="text-sm leading-6 text-muted-foreground">Include product names, phrases that must stay unchanged, audience, and the tone that should be avoided.</p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-3">
                            <Label class="flex items-center gap-2"><MessageSquare class="h-4 w-4" /> Tone of voice</Label>
                            <button
                                v-for="tone in [TranslationToneE.INFORMAL, TranslationToneE.NEUTRAL, TranslationToneE.FORMAL]"
                                :key="tone"
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg border bg-white px-4 py-3 text-left capitalize transition hover:border-emerald-300"
                                :class="{ 'border-emerald-600 bg-emerald-50 text-emerald-900': form.translation_tone === tone }"
                                @click="form.translation_tone = tone"
                            >
                                {{ tone.toLowerCase() }}
                                <span v-if="form.translation_tone === tone" class="h-2 w-2 rounded-full bg-emerald-600" />
                            </button>
                        </div>
                        <div class="space-y-3">
                            <Label class="flex items-center gap-2"><Users class="h-4 w-4" /> Target audience</Label>
                            <button
                                v-for="audience in [TranslationAudienceE.GENERAL, TranslationAudienceE.TECHNICAL, TranslationAudienceE.NONTECHNICAL]"
                                :key="audience"
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg border bg-white px-4 py-3 text-left capitalize transition hover:border-emerald-300"
                                :class="{ 'border-emerald-600 bg-emerald-50 text-emerald-900': form.translation_audience === audience }"
                                @click="form.translation_audience = audience"
                            >
                                {{ audience.toLowerCase().replace('-', ' ') }}
                                <span v-if="form.translation_audience === audience" class="h-2 w-2 rounded-full bg-emerald-600" />
                            </button>
                        </div>
                    </div>

                    <ConfirmAction description="Save translation context?" :action="submitForm" :loading="form.processing">
                        <Button :processing="form.processing"><Save class="mr-2 h-4 w-4" /> Save configuration</Button>
                    </ConfirmAction>
                </CardContent>
            </Card>

            <Alert v-else-if="credential" class="border-blue-200 bg-blue-50">
                <Zap class="h-5 w-5 text-blue-600" />
                <AlertTitle>Neural machine translation</AlertTitle>
                <AlertDescription
                    >This provider translates directly and does not use tone, audience, or website context. Use glossary rules for brand terms and phrases that must stay consistent.</AlertDescription
                >
            </Alert>
        </div>
    </ProjectLayout>
</template>

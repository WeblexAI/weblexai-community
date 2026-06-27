<script setup lang="ts">
import ConfirmAction from '@/components/ConfirmAction.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import useProject from '@/composables/useProject';
import { TranslationAudienceE, TranslationToneE } from '@/enums';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertCircle, BrainCircuit, MessageSquare, Save, Sparkles, Users, Zap } from 'lucide-vue-next';

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
                <AlertDescription>An administrator must assign a translation provider before automatic translations can run.</AlertDescription>
            </Alert>

            <Card v-else class="overflow-hidden border-0 shadow-md">
                <CardHeader class="bg-gradient-to-r from-emerald-50 to-white">
                    <div class="flex items-start gap-4">
                        <div class="rounded-xl p-3" :class="isLlm ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700'">
                            <BrainCircuit v-if="isLlm" class="h-6 w-6" />
                            <Zap v-else class="h-6 w-6" />
                        </div>
                        <div>
                            <CardTitle>{{ credential.name }}</CardTitle>
                            <CardDescription class="mt-1">{{ credential.provider_label }} · {{ credential.provider_type }}</CardDescription>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <Card v-if="credential && isLlm" class="border-purple-100 bg-gradient-to-br from-white to-purple-50/40 shadow-md">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-purple-800">
                        <Sparkles class="h-5 w-5" />
                        Fine-tune your AI model
                    </CardTitle>
                    <CardDescription>Give the provider enough context to produce consistent, brand-aligned translations.</CardDescription>
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
                            placeholder="Describe your website, terminology, products, and brand voice."
                        />
                        <InputError :message="form.errors.website_description" />
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-3">
                            <Label class="flex items-center gap-2"><MessageSquare class="h-4 w-4" /> Tone of voice</Label>
                            <button
                                v-for="tone in [TranslationToneE.INFORMAL, TranslationToneE.NEUTRAL, TranslationToneE.FORMAL]"
                                :key="tone"
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg border bg-white px-4 py-3 text-left capitalize"
                                :class="{ 'border-purple-500 bg-purple-50 text-purple-800': form.translation_tone === tone }"
                                @click="form.translation_tone = tone"
                            >
                                {{ tone.toLowerCase() }}
                                <span v-if="form.translation_tone === tone" class="h-2 w-2 rounded-full bg-purple-500" />
                            </button>
                        </div>
                        <div class="space-y-3">
                            <Label class="flex items-center gap-2"><Users class="h-4 w-4" /> Target audience</Label>
                            <button
                                v-for="audience in [TranslationAudienceE.GENERAL, TranslationAudienceE.TECHNICAL, TranslationAudienceE.NONTECHNICAL]"
                                :key="audience"
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg border bg-white px-4 py-3 text-left capitalize"
                                :class="{ 'border-purple-500 bg-purple-50 text-purple-800': form.translation_audience === audience }"
                                @click="form.translation_audience = audience"
                            >
                                {{ audience.toLowerCase().replace('-', ' ') }}
                                <span v-if="form.translation_audience === audience" class="h-2 w-2 rounded-full bg-purple-500" />
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
                <AlertDescription>This provider translates directly and does not use AI context, tone, or audience settings.</AlertDescription>
            </Alert>
        </div>
    </ProjectLayout>
</template>

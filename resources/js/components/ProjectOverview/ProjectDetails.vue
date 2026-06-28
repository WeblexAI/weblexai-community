<script setup lang="ts">
import OverviewProjectDetailsSkeleton from '@/components/Skeletons/OverviewProjectDetailsSkeleton.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import useAuthorization from '@/composables/useAuthorization';
import useProject from '@/composables/useProject';
import { overviewGetProjectDetails } from '@/lib/api';
import { routeWithProject } from '@/lib/helpers';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Ban, Book, CheckCircle2, FileText, FileX, Languages, Rocket } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const { canManageSettings, canManageContent } = useAuthorization();

const project = useProject();
const data = ref({
    languagesCount: 0,
    translationsCount: 0,
    translatedWordsCount: 0,
    manualTranslationsCount: 0,
    glossariesCount: 0,
    excludedBlocksCount: 0,
    blacklistedPagesCount: 0,
});
const loading = ref(true);

onMounted(async () => {
    loading.value = true;
    const response = await overviewGetProjectDetails();
    data.value = response;
    loading.value = false;
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">{{ project.name }}</h2>
                <p class="text-muted-foreground">{{ project.slug }}</p>
            </div>
            <Button v-if="canManageSettings" as-child variant="outline">
                <Link :href="routeWithProject('projects.settings')">Project Settings</Link>
            </Button>
        </div>

        <OverviewProjectDetailsSkeleton v-if="loading" />

        <div v-else class="space-y-6">
            <Card v-if="canManageSettings && (!project.is_integrated || !project.provider_credential || !project.first_language)" class="border-emerald-200 bg-emerald-50/60">
                <CardContent class="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                            <Rocket class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="font-semibold text-slate-950">Finish project setup</div>
                            <p class="mt-1 text-sm leading-6 text-slate-700">
                                Confirm provider, API key, accepted origin, target language, and browser SDK verification before handing this project to a website team.
                            </p>
                        </div>
                    </div>
                    <Button as-child>
                        <Link :href="route('projects.setup', project.slug)">Open setup checklist</Link>
                    </Button>
                </CardContent>
            </Card>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Translations</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ data.translationsCount }}</div>
                        <p class="text-xs text-muted-foreground">Across {{ data.languagesCount }} languages</p>
                        <Link v-if="canManageContent" :href="routeWithProject('projects.languages.index')" class="mt-2 inline-block text-xs text-primary hover:underline"> Manage translations </Link>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Translated Words</CardTitle>
                        <Languages class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ data.translatedWordsCount }}</div>
                        <p class="text-xs text-muted-foreground">Total word count</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Manual Reviews</CardTitle>
                        <CheckCircle2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ data.manualTranslationsCount }}</div>
                        <p class="text-xs text-muted-foreground">Manually approved</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Languages</CardTitle>
                        <Languages class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ data.languagesCount }}</div>
                        <Link v-if="canManageContent" :href="routeWithProject('projects.languages.index')" class="mt-2 inline-block text-xs text-primary hover:underline"> Add language </Link>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <Card v-if="canManageContent" class="group cursor-pointer transition-colors hover:bg-gray-50/50" @click="$inertia.visit(routeWithProject('projects.glossaries.index'))">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base"> <Book class="h-4 w-4 text-primary" /> Glossary </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-1 text-2xl font-bold">{{ data.glossariesCount }}</div>
                        <p class="mb-4 text-sm text-muted-foreground">Rules configured</p>
                        <div class="flex items-center text-sm font-medium text-primary transition-transform group-hover:translate-x-1">
                            Manage Glossary
                            <ArrowRight class="ml-1 h-3 w-3" />
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="canManageContent" class="group cursor-pointer transition-colors hover:bg-gray-50/50" @click="$inertia.visit(routeWithProject('projects.excluded-blocks.index'))">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base"> <Ban class="h-4 w-4 text-red-500" /> Exclusions </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-1 text-2xl font-bold">{{ data.excludedBlocksCount }}</div>
                        <p class="mb-4 text-sm text-muted-foreground">Excluded blocks</p>
                        <div class="flex items-center text-sm font-medium text-primary transition-transform group-hover:translate-x-1">
                            Manage Exclusions
                            <ArrowRight class="ml-1 h-3 w-3" />
                        </div>
                    </CardContent>
                </Card>

                <Card
                    v-if="canManageContent && project.first_language"
                    class="group cursor-pointer transition-colors hover:bg-gray-50/50"
                    @click="$inertia.visit(routeWithProject('projects.languages.pages.index', { language: project.first_language.iso_2 }) + '?filter[status]=blacklisted')"
                >
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base"> <FileX class="h-4 w-4 text-gray-500" /> Blacklisted Pages </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-1 text-2xl font-bold">{{ data.blacklistedPagesCount }}</div>
                        <p class="mb-4 text-sm text-muted-foreground">Pages ignored</p>
                        <div class="flex items-center text-sm font-medium text-primary transition-transform group-hover:translate-x-1">
                            Manage Blacklist
                            <ArrowRight class="ml-1 h-3 w-3" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

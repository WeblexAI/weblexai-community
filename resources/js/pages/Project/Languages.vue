<script setup lang="ts">
import ConfirmAction from '@/components/ConfirmAction.vue';
import AttachLanguageDialog from '@/components/language/AttachLanguageDialog.vue';
import LanguageOptionsPopover from '@/components/language/LanguageOptionsPopover.vue';
import LanguageWithFlag from '@/components/language/LanguageWithFlag.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { SearchInput } from '@/components/ui/search-input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import useAuthorization from '@/composables/useAuthorization';
import useProject from '@/composables/useProject';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { routeWithProject, toastInfo, toastResponse } from '@/lib/helpers';
import { LanguageI, PageI, ProjectLanguagePivot } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { BookA, EllipsisVertical, Languages, MoveRight, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const { canManageSettings } = useAuthorization();

type Props = {
    languages: Array<LanguageI & { pivot: ProjectLanguagePivot }>;
    languagesToAttach: LanguageI[];
    indexPage: PageI | null;
};

const props = defineProps<Props>();
const project = useProject();
const searchQuery = ref('');

const filteredLanguages = computed(() => {
    if (!searchQuery.value) return props.languages;
    const query = searchQuery.value.toLowerCase();
    return props.languages.filter((l) => l.name.toLowerCase().includes(query) || l.iso_2.toLowerCase().includes(query));
});

import { Badge } from '@/components/ui/badge';

function visitLanguageTranslations(language: LanguageI) {
    if (props.indexPage) {
        router.visit(
            routeWithProject('projects.languages.show', {
                language: language.iso_2,
            }) +
                '?page=' +
                props.indexPage.domain,
        );
    } else {
        toastInfo("Looks like your project setup isn't finished. Head over to the Project Setup page to complete the WeblexAI integration.");
    }
}

const enablingLanguage = ref(false);

function enableLanguage(language: LanguageI) {
    enablingLanguage.value = true;
    router.post(
        routeWithProject('projects.languages.enable', { language: language.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: (response) => {
                toastResponse(response);
            },
            onFinish: () => {
                enablingLanguage.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Languages" />
    <ProjectLayout page-title="Languages">
        <TooltipProvider>
            <div class="animate__animated animate__fadeIn mx-auto max-w-6xl space-y-6">
                <div class="flex flex-col items-start justify-between gap-6 md:flex-row">
                    <div class="space-y-2">
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Project Languages</h2>
                        <p class="max-w-2xl leading-relaxed text-muted-foreground">Manage the languages available for your project. Add new target languages and monitor translation progress.</p>
                    </div>
                    <AttachLanguageDialog v-if="canManageSettings" :languages="languagesToAttach">
                        <Button class="bg-primary shadow-lg shadow-primary/20 transition-all duration-300 hover:bg-primary/90">
                            <Plus class="mr-2 h-4 w-4" />
                            Attach Language
                        </Button>
                    </AttachLanguageDialog>
                </div>

                <Card class="overflow-hidden border-none bg-white/80 shadow-sm backdrop-blur-sm">
                    <CardHeader class="border-b border-gray-100 bg-gray-50/50 pb-4">
                        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                            <div class="w-full sm:w-72">
                                <SearchInput v-model="searchQuery" placeholder="Search languages..." />
                            </div>

                            <div class="text-sm text-muted-foreground">Showing {{ filteredLanguages.length }} of {{ languages.length }} languages</div>
                        </div>
                    </CardHeader>

                    <CardContent class="p-0">
                        <Table>
                            <TableHeader class="bg-gray-50/50">
                                <TableRow class="border-gray-100 hover:bg-transparent">
                                    <TableHead class="pl-6 font-semibold text-gray-600">Translation Pair</TableHead>
                                    <TableHead class="text-right font-semibold text-gray-600">Total Words</TableHead>
                                    <TableHead class="text-right font-semibold text-gray-600">Manual Translations </TableHead>
                                    <TableHead class="w-[80px] text-center font-semibold text-gray-600">View</TableHead>
                                    <TableHead class="w-[60px] text-right font-semibold text-gray-600"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="language in filteredLanguages" :key="`language-${language.id}`" class="group cursor-pointer border-gray-100 transition-colors hover:bg-gray-50/50">
                                    <TableCell class="py-4 pl-6" @click="visitLanguageTranslations(language)">
                                        <div class="flex items-center gap-4">
                                            <div class="flex items-center gap-3 rounded-full border border-gray-100 bg-white px-3 py-1.5 shadow-sm">
                                                <LanguageWithFlag :language="project.original_language as LanguageI" class="font-medium" />
                                                <MoveRight class="h-4 w-4 text-gray-400" />
                                                <LanguageWithFlag :language="language" class="font-medium" />
                                            </div>
                                            <div v-if="language.pivot?.is_disabled" class="flex items-center gap-2">
                                                <Badge variant="destructive" class="h-6 text-xs">Disabled</Badge>
                                                <ConfirmAction
                                                    v-if="canManageSettings"
                                                    :action="() => enableLanguage(language)"
                                                    :loading="enablingLanguage"
                                                    :description="`Re-enable ${language.name}? This will consume one of your available language slots.`"
                                                    :close-after-action="true"
                                                >
                                                    <Button size="sm" variant="outline" class="h-6 text-xs" @click.stop> Enable </Button>
                                                </ConfirmAction>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right font-medium text-gray-900" @click="visitLanguageTranslations(language)">
                                        {{ language.total_translated_words ?? 0 }}
                                    </TableCell>
                                    <TableCell class="text-right font-medium text-gray-900" @click="visitLanguageTranslations(language)">
                                        {{ language.manual_translated_words ?? 0 }}
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 text-gray-500 hover:bg-primary/10 hover:text-primary"
                                                    @click.stop="visitLanguageTranslations(language)"
                                                >
                                                    <BookA class="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>View translations for {{ language.name }}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TableCell>
                                    <TableCell class="pr-4 text-right">
                                        <LanguageOptionsPopover :language="language">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-gray-400 hover:text-gray-700">
                                                <EllipsisVertical class="h-4 w-4" />
                                            </Button>
                                        </LanguageOptionsPopover>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="!filteredLanguages.length">
                                    <TableCell colspan="5" class="h-64 text-center">
                                        <div class="flex flex-col items-center justify-center text-muted-foreground">
                                            <div class="mb-4 rounded-full bg-gray-50 p-4">
                                                <Languages class="h-8 w-8 text-gray-400" />
                                            </div>
                                            <h3 class="mb-1 text-lg font-medium text-gray-900">
                                                {{ languages.length ? 'No languages found' : 'No languages attached' }}
                                            </h3>
                                            <p class="mx-auto mb-4 max-w-sm text-sm">
                                                {{ languages.length ? 'Try adjusting your search query.' : 'Start by attaching a language to your project.' }}
                                            </p>
                                            <AttachLanguageDialog v-if="!languages.length" :languages="languagesToAttach">
                                                <Button variant="outline" class="gap-2">
                                                    <Plus class="h-4 w-4" />
                                                    Attach First Language
                                                </Button>
                                            </AttachLanguageDialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </TooltipProvider>
    </ProjectLayout>
</template>

<style scoped></style>

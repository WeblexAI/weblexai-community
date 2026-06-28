<script setup lang="ts">
import ImportTranslationsDialog from '@/components/language/ImportTranslationsDialog.vue';
import LanguageWithFlag from '@/components/language/LanguageWithFlag.vue';
import Pagination from '@/components/Pagination.vue';
import TranslationCard from '@/components/translation/TranslationCard.vue';
import TranslationsFilter from '@/components/translation/TranslationsFilter.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import { TooltipProvider } from '@/components/ui/tooltip';
import useAuthorization from '@/composables/useAuthorization';
import { useFilterQuery } from '@/inertia-essentials/filter-sqb';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { LanguageI, PageI, PaginatedDataI, TranslationI } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ChevronDown, Download, FileText, Filter as FilterIcon, MoveRight, Upload, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const { canManageContent } = useAuthorization();

type Props = {
    language: LanguageI;
    languages: LanguageI[];
    originalLanguage: LanguageI;
    translations: PaginatedDataI<TranslationI>;
    pages: PageI[];
    page: PageI;
    indexPage: PageI | null;
};

const props = defineProps<Props>();
const filterForm = useForm({
    q: '',
    quality: [],
    status: [],
});

function visitLangPageTranslations(domain: string) {
    router.visit(
        routeWithProject('projects.languages.show', {
            language: props.language.iso_2,
        }) +
            '?page=' +
            domain,
    );
}

function visitLanguageTranslations(language: LanguageI) {
    if (props.indexPage) {
        router.visit(
            routeWithProject('projects.languages.show', {
                language: language.iso_2,
            }) +
                '?page=' +
                props.indexPage.domain,
        );
    }
}

const { resetFilterKey, runFilter } = useFilterQuery({ form: filterForm });

const exporting = ref(false);

const activeFilters = computed(() => {
    const filters: Record<string, any> = {};

    if (filterForm.q) {
        filters.q = filterForm.q;
    }

    if (filterForm.quality && filterForm.quality.length > 0) {
        filters.quality = filterForm.quality;
    }

    if (filterForm.status && filterForm.status.length > 0) {
        filters.status = filterForm.status;
    }

    return filters;
});

// Watch for search query changes - trigger filter when cleared
watch(
    () => filterForm.q,
    (newVal, oldVal) => {
        if (oldVal && !newVal) {
            // Search was cleared
            runFilter();
        }
    },
);

function handleExport() {
    router.post(
        routeWithProject('projects.translations.export'),
        {
            page_id: props.page.id,
            target_lang_id: props.language.id,
        },
        {
            preserveScroll: true,
            onSuccess: (res) => toastResponse(res),
            onStart: () => (exporting.value = true),
            onFinish: () => (exporting.value = false),
        },
    );
}
</script>

<template>
    <Head title="Language Translations" />
    <ProjectLayout :page-title="`${originalLanguage.name} To ${language.name} Translations`">
        <TooltipProvider>
            <div class="animate__animated animate__fadeIn mx-auto max-w-7xl space-y-6">
                <div class="flex flex-col items-start justify-between gap-6 md:flex-row">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Translations</h2>
                            <div class="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 shadow-sm">
                                <LanguageWithFlag :language="originalLanguage" class="text-sm font-medium" />
                                <MoveRight class="h-4 w-4 text-gray-400" />
                                <LanguageWithFlag :language="language" class="text-sm font-medium" />
                            </div>
                        </div>
                        <p class="max-w-2xl leading-relaxed text-muted-foreground">
                            Manage and review translations for <strong>{{ page.path }}</strong>
                        </p>
                    </div>

                    <Popover>
                        <PopoverTrigger as-child>
                            <Button variant="outline" class="gap-2 border-gray-200 bg-white shadow-sm hover:bg-gray-50">
                                <LanguageWithFlag :language="language" class="text-sm font-medium" />
                                <ChevronDown v-if="languages.length" class="h-4 w-4 text-gray-500 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent v-if="languages.length" class="w-[250px] p-1" align="end">
                            <div class="px-2 py-1.5 text-xs font-semibold text-muted-foreground">Switch Target Language</div>
                            <div class="my-1 h-px bg-gray-100" />
                            <div class="max-h-[300px] overflow-y-auto">
                                <div
                                    v-for="lang in languages"
                                    :key="`lang-${lang.id}`"
                                    class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-2 text-sm transition-colors hover:bg-gray-100"
                                    :class="{ 'bg-primary/5 text-primary': lang.id === language.id }"
                                    @click="visitLanguageTranslations(lang)"
                                >
                                    <LanguageWithFlag :language="originalLanguage" class="text-xs" />
                                    <MoveRight class="h-3 w-3 text-gray-400" />
                                    <LanguageWithFlag :language="lang" class="text-xs" />
                                    <span v-if="lang.id === language.id" class="ml-auto text-xs font-medium text-primary">Active</span>
                                </div>
                            </div>
                        </PopoverContent>
                    </Popover>
                </div>

                <div class="flex items-center gap-3">
                    <TranslationsFilter :form="filterForm" />
                </div>

                <!-- Main Content -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                    <!-- Sidebar - Pages List -->
                    <div class="lg:col-span-1">
                        <Card class="border-none bg-white/80 shadow-sm backdrop-blur-sm">
                            <CardHeader class="pb-3">
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4 text-primary" />
                                    <h3 class="text-sm font-semibold text-gray-900">Pages</h3>
                                    <Badge variant="outline" class="ml-auto text-xs">{{ pages.length }}</Badge>
                                </div>
                            </CardHeader>
                            <CardContent class="p-2">
                                <ScrollArea class="h-[calc(100vh-300px)]">
                                    <div class="space-y-1">
                                        <div
                                            v-for="page_ in pages"
                                            :key="`page-${page_.id}`"
                                            @click="visitLangPageTranslations(page_.domain)"
                                            class="cursor-pointer rounded-md px-3 py-2.5 text-sm transition-all duration-200"
                                            :class="{
                                                'bg-primary text-white shadow-sm': page_.id === page.id,
                                                'text-gray-700 hover:bg-gray-100': page_.id !== page.id,
                                            }"
                                        >
                                            <div class="truncate font-medium">{{ page_.path }}</div>
                                            <div v-if="page_.translations_count" class="mt-0.5 text-xs opacity-75">{{ page_.translations_count }} translations</div>
                                        </div>
                                    </div>
                                </ScrollArea>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Main Content - Translations -->
                    <div class="lg:col-span-3">
                        <Card class="border-none bg-white/80 shadow-sm backdrop-blur-sm">
                            <CardHeader class="border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ page.path }}</h3>
                                        <p class="mt-1 text-sm text-muted-foreground">{{ page.translations_count }} translation{{ page.translations_count !== 1 ? 's' : '' }}</p>
                                    </div>

                                    <!-- Import/Export Actions -->
                                    <div v-if="canManageContent" class="flex gap-2">
                                        <ImportTranslationsDialog :target-language="language" :original-language="originalLanguage" :page="page">
                                            <Button size="sm" variant="outline" class="gap-2">
                                                <Upload class="h-4 w-4" />
                                                Import
                                            </Button>
                                        </ImportTranslationsDialog>
                                        <Button size="sm" variant="outline" class="gap-2" @click="handleExport" :disabled="exporting">
                                            <Download class="h-4 w-4" />
                                            Export
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>

                            <CardContent class="p-4">
                                <ScrollArea class="h-[calc(100vh-350px)]">
                                    <div class="space-y-4 pr-4">
                                        <TranslationCard v-for="translation in translations.data" :translation="translation" :key="`translation-${translation.id}`" />
                                    </div>

                                    <div v-if="!translations.data?.length" class="flex flex-col items-center justify-center py-16 text-center">
                                        <div class="mb-4 rounded-full bg-gray-50 p-4">
                                            <FileText class="h-8 w-8 text-gray-400" />
                                        </div>
                                        <h3 class="mb-2 text-lg font-medium text-gray-900">No translations found</h3>
                                        <p class="max-w-md text-sm text-muted-foreground">
                                            To preview and sync translations, open your website, navigate to
                                            <strong class="text-gray-900">{{ page.path }}</strong> and switch the language selector to <strong class="text-gray-900">{{ language.name }}</strong
                                            >. Translatable content will appear here in real time.
                                        </p>
                                    </div>
                                </ScrollArea>

                                <div v-if="translations.next_page_url || translations.prev_page_url" class="mt-6 border-t border-gray-100 pt-4">
                                    <Pagination :links="translations.links" />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="translate-y-full opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-full opacity-0"
                >
                    <div v-if="Object.keys(activeFilters).length > 0" class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 animate-in slide-in-from-bottom-4">
                        <Card class="border-primary/20 bg-white/95 shadow-2xl backdrop-blur-md">
                            <CardContent class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <FilterIcon class="h-4 w-4" />
                                        <span class="font-medium">Active Filters:</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Badge v-if="activeFilters.hasOwnProperty('q')" variant="secondary" class="gap-2 pr-1">
                                            <span class="text-xs"
                                                >Search: <strong>{{ activeFilters.q }}</strong></span
                                            >
                                            <Button variant="ghost" size="icon" class="h-4 w-4 p-0 hover:bg-destructive/10" @click="resetFilterKey('q')">
                                                <X class="h-3 w-3 text-destructive" />
                                            </Button>
                                        </Badge>

                                        <Badge v-if="activeFilters.hasOwnProperty('quality')" variant="secondary" class="gap-2 pr-1">
                                            <span class="text-xs"
                                                >Quality: <strong>{{ activeFilters.quality.join(', ') }}</strong></span
                                            >
                                            <Button variant="ghost" size="icon" class="h-4 w-4 p-0 hover:bg-destructive/10" @click="resetFilterKey('quality')">
                                                <X class="h-3 w-3 text-destructive" />
                                            </Button>
                                        </Badge>

                                        <Badge v-if="activeFilters.hasOwnProperty('status')" variant="secondary" class="gap-2 pr-1">
                                            <span class="text-xs"
                                                >Status: <strong>{{ activeFilters.status.join(', ') }}</strong></span
                                            >
                                            <Button variant="ghost" size="icon" class="h-4 w-4 p-0 hover:bg-destructive/10" @click="resetFilterKey('status')">
                                                <X class="h-3 w-3 text-destructive" />
                                            </Button>
                                        </Badge>
                                    </div>

                                    <Button variant="ghost" size="sm" class="ml-2 h-7 text-xs" @click="resetFilterKey('all')"> Clear All </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </Transition>
            </div>
        </TooltipProvider>
    </ProjectLayout>
</template>

<style scoped></style>

<script setup lang="ts">
import ConfirmAction from '@/components/ConfirmAction.vue';
import LanguageWithFlag from '@/components/language/LanguageWithFlag.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { SearchInput } from '@/components/ui/search-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useSelectTableItems } from '@/composables/useSelectTableItems';
import { useFilterQuery } from '@/inertia-essentials/filter-sqb';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { LanguageI, PageI, PaginatedDataI } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { BookA, ChevronDown, FileText, Filter, Power, PowerOff } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

type Props = {
    language: LanguageI;
    languages: LanguageI[];
    originalLanguage: LanguageI;
    pages: PaginatedDataI<PageI>;
};

const props = defineProps<Props>();
const { selectedItems, selectItem, toggleAll } = useSelectTableItems();
const isProcessing = ref(false);

const filterForm = useForm({
    q: '',
    status: 'all',
});

const { runFilter, filteredItems } = useFilterQuery({
    form: filterForm,
    buildQuery: (qBuilder) => {
        if (filterForm.q) qBuilder.filter('q', filterForm.q);
        if (filterForm.status && filterForm.status !== 'all') qBuilder.filter('status', filterForm.status);
    },
});

let debounceTimer: ReturnType<typeof setTimeout>;

function onSearchInput() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        runFilter();
    }, 500);
}

function filterByStatus(value: unknown) {
    if (typeof value !== 'string') return;
    filterForm.status = value;
    runFilter();
}

function visitLangPageTranslations(domain: string) {
    router.visit(
        routeWithProject('projects.languages.show', {
            language: props.language.iso_2,
        }) +
            '?page=' +
            domain,
    );
}

function blacklist(pageId: number) {
    router.post(
        routeWithProject('projects.pages.blacklist', { page: pageId }),
        {},
        {
            preserveScroll: true,
            onStart: () => (isProcessing.value = true),
            onFinish: () => (isProcessing.value = false),
            onSuccess: (res) => toastResponse(res),
        },
    );
}

function blacklistBulk(is_blacklisted: boolean) {
    router.post(
        routeWithProject('projects.pages.blacklist.bulk'),
        {
            page_ids: selectedItems.value,
            is_blacklisted: is_blacklisted,
        },
        {
            preserveScroll: true,
            onStart: () => (isProcessing.value = true),
            onFinish: () => (isProcessing.value = false),
            onSuccess: (res) => {
                toastResponse(res);
                toggleAll(false, props.pages.data, 'id');
            },
        },
    );
}

onMounted(() => {
    filterForm.status = filteredItems.status ?? 'all';
    filterForm.q = filteredItems.q ?? '';
});
</script>

<template>
    <Head title="Pages Translations" />
    <ProjectLayout page-title="Pages Translations">
        <TooltipProvider>
            <div class="animate__animated animate__fadeIn mx-auto max-w-6xl space-y-6">
                <div class="flex flex-col items-start justify-between gap-6 md:flex-row">
                    <div class="space-y-2">
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Pages & Translations</h2>
                        <p class="max-w-2xl leading-relaxed text-muted-foreground">
                            Manage translations for individual pages. Monitor translation progress and control visibility for the
                            <span class="font-medium text-gray-900">{{ language.name }}</span> language.
                        </p>
                    </div>

                    <Popover>
                        <PopoverTrigger as-child>
                            <Button variant="outline" class="h-10 gap-2 border-gray-200 bg-white px-4 shadow-sm hover:bg-gray-50 hover:text-gray-900">
                                <LanguageWithFlag :language="language" class="text-sm font-medium" />
                                <ChevronDown class="h-4 w-4 text-gray-500 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[200px] p-1" align="end">
                            <div class="px-2 py-1.5 text-xs font-semibold text-muted-foreground">Switch Language</div>
                            <div class="my-1 h-px bg-gray-100" />
                            <div class="max-h-[300px] overflow-y-auto">
                                <div
                                    v-for="lang in languages"
                                    :key="`lang-${lang.id}`"
                                    class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-2 text-sm transition-colors hover:bg-gray-100"
                                    :class="{ 'bg-primary/5 text-primary': lang.id === language.id }"
                                    @click="router.visit(routeWithProject('projects.languages.pages.index', { language: lang.iso_2 }))"
                                >
                                    <LanguageWithFlag :language="lang" />
                                    <span v-if="lang.id === language.id" class="ml-auto text-xs font-medium text-primary">Active</span>
                                </div>
                            </div>
                        </PopoverContent>
                    </Popover>
                </div>

                <Card class="overflow-hidden border-none bg-white/80 shadow-sm backdrop-blur-sm">
                    <CardHeader class="border-b border-gray-100 bg-gray-50/50 pb-4">
                        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                            <div class="w-full sm:w-72">
                                <SearchInput v-model="filterForm.q" placeholder="Search pages..." @input="onSearchInput" />
                            </div>

                            <div class="flex w-full items-center gap-2 sm:w-auto">
                                <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-muted-foreground">
                                    <Filter class="h-4 w-4" />
                                    <span>Filter by:</span>
                                </div>
                                <Select :model-value="filterForm.status" @update:model-value="filterByStatus">
                                    <SelectTrigger class="w-[150px] border-gray-200 bg-white">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Status</SelectItem>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="blacklisted">Blacklisted</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="p-0">
                        <div v-if="selectedItems.length" class="flex animate-in items-center justify-between border-b border-primary/10 bg-primary/5 p-2 px-4 slide-in-from-top-2">
                            <span class="text-sm font-medium text-primary">{{ selectedItems.length }} page(s) selected</span>
                            <div class="flex gap-2">
                                <ConfirmAction
                                    title="Blacklist Selected Pages"
                                    description="Are you sure you want to blacklist the selected pages? All translations for these pages will be permanently deleted and they will no longer be available for translation."
                                    :action="() => blacklistBulk(true)"
                                    :loading="isProcessing"
                                    variant="destructive"
                                >
                                    <Button size="sm" variant="destructive" class="h-8 text-xs shadow-sm">
                                        <PowerOff class="mr-2 h-3 w-3" />
                                        Blacklist Selected
                                    </Button>
                                </ConfirmAction>
                                <ConfirmAction
                                    title="Activate Selected Pages"
                                    description="Are you sure you want to activate the selected pages? They will be available for translation again."
                                    :action="() => blacklistBulk(false)"
                                    :loading="isProcessing"
                                    variant="success"
                                >
                                    <Button size="sm" variant="outline" class="h-8 bg-white text-xs shadow-sm hover:border-green-200 hover:bg-green-50 hover:text-green-600">
                                        <Power class="mr-2 h-3 w-3" />
                                        Activate Selected
                                    </Button>
                                </ConfirmAction>
                            </div>
                        </div>

                        <Table>
                            <TableHeader class="bg-gray-50/50">
                                <TableRow class="border-gray-100 hover:bg-transparent">
                                    <TableHead class="w-[50px]">
                                        <Checkbox
                                            :model-value="selectedItems.length > 0 && selectedItems.length === pages.data?.length"
                                            @update:model-value="(val) => toggleAll(val as boolean, pages.data, 'id')"
                                            class="border-gray-300 data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                                        />
                                    </TableHead>
                                    <TableHead class="font-semibold text-gray-600">Page Path</TableHead>
                                    <TableHead class="font-semibold text-gray-600">Translation Status</TableHead>
                                    <TableHead class="text-right font-semibold text-gray-600">Word Count</TableHead>
                                    <TableHead class="w-[120px] text-right font-semibold text-gray-600">Actions </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="page in pages.data" :key="`page-${page.id}`" class="group border-gray-100 transition-colors hover:bg-gray-50/50">
                                    <TableCell>
                                        <Checkbox
                                            :model-value="selectedItems.includes(page.id)"
                                            @update:model-value="(val) => selectItem(val as boolean, page.id)"
                                            class="border-gray-300 data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-md bg-gray-100 p-2 text-gray-500 transition-all group-hover:bg-white group-hover:shadow-sm">
                                                <FileText class="h-4 w-4" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-900">{{ page.path }}</span>
                                                <span class="max-w-[200px] truncate text-xs text-muted-foreground">{{ page.domain }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge v-if="page.is_blacklisted" variant="destructive" class="border-red-200 bg-red-50 font-normal text-red-700 hover:bg-red-100"> Blacklisted </Badge>
                                        <Badge v-else variant="outline" class="border-green-200 bg-green-50 font-normal text-green-700"> Active </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex flex-col items-end gap-0.5">
                                            <span class="text-sm font-medium text-gray-900">{{ page.total_translated_words ?? 0 }}</span>
                                            <span class="text-xs text-muted-foreground"> {{ page.manual_translated_words ?? 0 }} manual </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        class="h-8 w-8 text-gray-500 hover:bg-primary/10 hover:text-primary"
                                                        @click="visitLangPageTranslations(page.domain)"
                                                    >
                                                        <BookA class="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>View Translations</TooltipContent>
                                            </Tooltip>

                                            <Tooltip v-if="!page.is_blacklisted">
                                                <TooltipTrigger as-child>
                                                    <ConfirmAction
                                                        title="Blacklist Page"
                                                        description="Are you sure you want to blacklist this page? All translations for this page will be permanently deleted and the page will no longer be available for translation."
                                                        :action="() => blacklist(page.id)"
                                                        :loading="isProcessing"
                                                        variant="destructive"
                                                    >
                                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-gray-500 hover:bg-destructive/10 hover:text-destructive">
                                                            <PowerOff class="h-4 w-4" />
                                                        </Button>
                                                    </ConfirmAction>
                                                </TooltipTrigger>
                                                <TooltipContent>Blacklist Page</TooltipContent>
                                            </Tooltip>

                                            <Tooltip v-else>
                                                <TooltipTrigger as-child>
                                                    <ConfirmAction
                                                        title="Activate Page"
                                                        description="Are you sure you want to activate this page? The page will be available for translation again."
                                                        :action="() => blacklist(page.id)"
                                                        :loading="isProcessing"
                                                        variant="success"
                                                    >
                                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-gray-500 hover:bg-green-50 hover:text-green-600">
                                                            <Power class="h-4 w-4" />
                                                        </Button>
                                                    </ConfirmAction>
                                                </TooltipTrigger>
                                                <TooltipContent>Activate Page</TooltipContent>
                                            </Tooltip>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="!pages.data.length">
                                    <TableCell colspan="5" class="h-64 text-center">
                                        <div class="flex flex-col items-center justify-center text-muted-foreground">
                                            <div class="mb-4 rounded-full bg-gray-50 p-4">
                                                <FileText class="h-8 w-8 text-gray-400" />
                                            </div>
                                            <h3 class="mb-1 text-lg font-medium text-gray-900">No pages found</h3>
                                            <p class="mx-auto max-w-sm text-sm">We couldn't find any pages matching your search criteria.</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>

                    <div v-if="pages.next_page_url || pages.prev_page_url" class="border-t border-gray-100 bg-gray-50/50 p-4">
                        <Pagination :links="pages.links" />
                    </div>
                </Card>
            </div>
        </TooltipProvider>
    </ProjectLayout>
</template>

<style scoped></style>

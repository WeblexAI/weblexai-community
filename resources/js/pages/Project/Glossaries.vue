<script setup lang="ts">
import ConfirmAction from '@/components/ConfirmAction.vue';
import AddGlossaryDialog from '@/components/glossary/AddGlossaryDialog.vue';
import EditGlossaryDialog from '@/components/glossary/EditGlossaryDialog.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { SearchInput } from '@/components/ui/search-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { TooltipProvider } from '@/components/ui/tooltip';
import useAuthorization from '@/composables/useAuthorization';
import { useSelectTableItems } from '@/composables/useSelectTableItems';
import { GlossaryRuleE } from '@/enums';
import { useFilterQuery } from '@/inertia-essentials/filter-sqb';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { GlossaryI, LanguageI, PaginatedDataI } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowRight, BookA, Filter, Globe, Pencil, Plus, Trash, Type } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const { canManageContent } = useAuthorization();

type Props = {
    languages: LanguageI[];
    glossaries: PaginatedDataI<GlossaryI>;
};

const props = defineProps<Props>();
const isDeletingGlossary = ref(false);
const { selectedItems, selectItem, toggleAll } = useSelectTableItems();

const deleteGlossary = (id: number) => {
    router.delete(routeWithProject('projects.glossaries.delete', { glossary: id }), {
        onStart: () => (isDeletingGlossary.value = true),
        onFinish: () => (isDeletingGlossary.value = false),
        onSuccess: (res) => toastResponse(res),
    });
};

const filterForm = useForm({
    q: '',
    language: 'all',
});

const { runFilter, filteredItems } = useFilterQuery({
    form: filterForm,
    buildQuery: (qBuilder) => {
        if (filterForm.q) qBuilder.filter('q', filterForm.q);
        if (filterForm.language && filterForm.language !== 'all') qBuilder.filter('language', filterForm.language);
    },
});

let debounceTimer: ReturnType<typeof setTimeout>;

function onSearchInput() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        runFilter();
    }, 500);
}

function filterByLanguage(value: unknown) {
    if (typeof value !== 'string') return;
    filterForm.language = value;
    runFilter();
}

function bulkDelete() {
    router.delete(routeWithProject('projects.glossaries.bulk-delete'), {
        data: { glossaries: selectedItems.value },
        onStart: () => (isDeletingGlossary.value = true),
        onFinish: () => (isDeletingGlossary.value = false),
        onSuccess: (res) =>
            toastResponse(res, () => {
                toggleAll(false, props.glossaries.data, 'id');
            }),
    });
}

onMounted(() => {
    filterForm.language = filteredItems.language ?? 'all';
    filterForm.q = filteredItems.q ?? '';
});
</script>

<template>
    <Head title="Glossaries" />
    <ProjectLayout page-title="Glossaries">
        <TooltipProvider>
            <div class="animate__animated animate__fadeIn mx-auto max-w-6xl space-y-6">
                <!-- Header Section -->
                <div class="flex flex-col items-start justify-between gap-6 md:flex-row">
                    <div class="space-y-2">
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Glossary Rules</h2>
                        <p class="max-w-2xl leading-relaxed text-muted-foreground">
                            Define specific translation rules to ensure consistency for your brand terms, technical jargon, or words that should never be translated.
                        </p>
                    </div>
                    <AddGlossaryDialog v-if="canManageContent" :languages="languages">
                        <Button class="bg-primary shadow-lg shadow-primary/20 transition-all duration-300 hover:bg-primary/90">
                            <Plus class="mr-2 h-4 w-4" />
                            Add Glossary Rule
                        </Button>
                    </AddGlossaryDialog>
                </div>

                <!-- Main Card -->
                <Card class="overflow-hidden border-none bg-white/80 shadow-sm backdrop-blur-sm">
                    <CardHeader class="border-b border-gray-100 bg-gray-50/50 pb-4">
                        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                            <!-- Search -->
                            <div class="w-full sm:w-72">
                                <SearchInput v-model="filterForm.q" placeholder="Search glossaries..." @input="onSearchInput" />
                            </div>

                            <!-- Filters -->
                            <div class="flex w-full items-center gap-2 sm:w-auto">
                                <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-muted-foreground">
                                    <Filter class="h-4 w-4" />
                                    <span>Filter by:</span>
                                </div>
                                <Select :model-value="filterForm.language" @update:model-value="filterByLanguage">
                                    <SelectTrigger class="w-[180px] border-gray-200 bg-white">
                                        <SelectValue placeholder="All Languages" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Languages</SelectItem>
                                        <SelectItem v-for="language in languages" :key="language.id" :value="language.iso_2">
                                            {{ language.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="p-0">
                        <!-- Bulk Actions Bar -->
                        <div v-if="selectedItems.length && canManageContent" class="flex animate-in items-center justify-between border-b border-red-100 bg-red-50/50 p-2 px-4 slide-in-from-top-2">
                            <span class="text-sm font-medium text-red-700">{{ selectedItems.length }} item(s) selected</span>
                            <ConfirmAction variant="destructive" description="Are you sure you want to delete the selected glossary rules?" :action="bulkDelete" :loading="isDeletingGlossary">
                                <Button size="sm" variant="destructive" class="h-8 text-xs shadow-sm">
                                    <Trash class="mr-2 h-3 w-3" />
                                    Delete Selected
                                </Button>
                            </ConfirmAction>
                        </div>

                        <Table>
                            <TableHeader class="bg-gray-50/50">
                                <TableRow class="border-gray-100 hover:bg-transparent">
                                    <TableHead v-if="canManageContent" class="w-[50px]">
                                        <Checkbox
                                            :model-value="selectedItems.length > 0 && selectedItems.length === glossaries.data?.length"
                                            @update:model-value="(val) => toggleAll(val as boolean, glossaries.data, 'id')"
                                            class="border-gray-300 data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                                        />
                                    </TableHead>
                                    <TableHead class="font-semibold text-gray-600">Rule Definition</TableHead>
                                    <TableHead class="w-[150px] font-semibold text-gray-600">Case Sensitivity </TableHead>
                                    <TableHead class="w-[200px] font-semibold text-gray-600">Languages</TableHead>
                                    <TableHead class="w-[100px] text-right font-semibold text-gray-600">Actions </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="glossary in glossaries.data" :key="`glossary-${glossary.id}`" class="group border-gray-100 transition-colors hover:bg-gray-50/50">
                                    <TableCell v-if="canManageContent">
                                        <Checkbox
                                            :model-value="selectedItems.includes(glossary.id)"
                                            @update:model-value="(val) => selectItem(val as boolean, glossary.id)"
                                            class="border-gray-300 data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-col gap-1.5">
                                            <div v-if="glossary.rule === GlossaryRuleE.ALWAYS_TRANSLATED" class="flex items-center gap-2 text-sm">
                                                <Badge variant="outline" class="border-blue-200 bg-blue-50 font-normal text-blue-700"> Always Translate </Badge>
                                                <span class="font-medium text-gray-900">{{ glossary.text }}</span>
                                                <ArrowRight class="h-3.5 w-3.5 text-gray-400" />
                                                <span class="font-medium text-gray-900">{{ glossary.translated }}</span>
                                            </div>
                                            <div v-else class="flex items-center gap-2 text-sm">
                                                <Badge variant="outline" class="border-orange-200 bg-orange-50 font-normal text-orange-700"> Never Translate </Badge>
                                                <span class="font-medium text-gray-900">{{ glossary.text }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <Type class="h-4 w-4 text-gray-400" />
                                            {{ glossary.is_case_sensitive ? 'Sensitive' : 'Insensitive' }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="glossary.is_all_languages" class="flex items-center gap-2 text-sm text-gray-600">
                                            <Globe class="h-4 w-4 text-gray-400" />
                                            <span>All Languages</span>
                                        </div>
                                        <div v-else class="flex flex-wrap gap-1">
                                            <Badge v-for="language in glossary.languages" :key="`gloss-lang-${language.id}`" variant="secondary" class="bg-gray-100 font-normal text-gray-700">
                                                {{ language.name }}
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell v-if="canManageContent" class="text-right">
                                        <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                            <EditGlossaryDialog :glossary="glossary" :languages="languages">
                                                <Button variant="ghost" size="icon" class="h-8 w-8 text-gray-500 hover:bg-primary/10 hover:text-primary">
                                                    <Pencil class="h-4 w-4" />
                                                </Button>
                                            </EditGlossaryDialog>
                                            <ConfirmAction
                                                description="Are you sure you want to remove this glossary rule?"
                                                variant="destructive"
                                                :action="() => deleteGlossary(glossary.id)"
                                                :loading="isDeletingGlossary"
                                            >
                                                <Button variant="ghost" size="icon" class="h-8 w-8 text-gray-500 hover:bg-destructive/10 hover:text-destructive">
                                                    <Trash class="h-4 w-4" />
                                                </Button>
                                            </ConfirmAction>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- Empty State -->
                                <TableRow v-if="!glossaries.data.length">
                                    <TableCell colspan="5" class="h-64 text-center">
                                        <div class="flex flex-col items-center justify-center text-muted-foreground">
                                            <div class="mb-4 rounded-full bg-gray-50 p-4">
                                                <BookA class="h-8 w-8 text-gray-400" />
                                            </div>
                                            <h3 class="mb-1 text-lg font-medium text-gray-900">No glossary rules yet</h3>
                                            <p class="mx-auto mb-4 max-w-sm text-sm">Create rules to control how specific terms are translated across your project.</p>
                                            <AddGlossaryDialog v-if="canManageContent" :languages="languages">
                                                <Button variant="outline" class="gap-2">
                                                    <Plus class="h-4 w-4" />
                                                    Add First Rule
                                                </Button>
                                            </AddGlossaryDialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>

                    <!-- Pagination -->
                    <div v-if="glossaries.next_page_url || glossaries.prev_page_url" class="border-t border-gray-100 bg-gray-50/50 p-4">
                        <Pagination :links="glossaries.links" />
                    </div>
                </Card>
            </div>
        </TooltipProvider>
    </ProjectLayout>
</template>

<style scoped></style>

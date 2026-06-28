<script setup lang="ts">
import ConfirmAction from '@/components/ConfirmAction.vue';
import AddExcludedBlockDialog from '@/components/ExcludedBlock/AddExcludedBlockDialog.vue';
import EditExcludedBlockDialog from '@/components/ExcludedBlock/EditExcludedBlockDialog.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { SearchInput } from '@/components/ui/search-input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import useAuthorization from '@/composables/useAuthorization';
import { useSelectTableItems } from '@/composables/useSelectTableItems';
import { useFilterQuery } from '@/inertia-essentials/filter-sqb';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { ExcludedBlockI, PaginatedDataI } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Ban, Pencil, Plus, ShieldAlert, Trash } from 'lucide-vue-next';
import { ref } from 'vue';

const { canManageContent } = useAuthorization();

type Props = {
    excludedBlocks: PaginatedDataI<ExcludedBlockI>;
};

defineProps<Props>();

const filterForm = useForm({
    q: '',
});

const { resetFilterKey, runFilter } = useFilterQuery({
    form: filterForm,
    paginationKey: 'page',
    buildQuery: (qBuilder) => {
        if (filterForm.q) qBuilder.filter('q', filterForm.q);
    },
});

const { selectedItems, selectItem, toggleAll } = useSelectTableItems();
let debounceTimer: ReturnType<typeof setTimeout>;

function clearFilter() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        if (!filterForm.q.length) {
            resetFilterKey();
            return;
        }
    }, 1000);
}

const isDeletingBlock = ref(false);

function deleteBlock(id: number) {
    router.delete(routeWithProject('projects.excluded-blocks.delete', { block: id }), {
        onSuccess: (res) => toastResponse(res),
        onStart: () => {
            isDeletingBlock.value = true;
        },
        onFinish: () => {
            isDeletingBlock.value = false;
        },
    });
}

function bulkDelete() {
    router.delete(routeWithProject('projects.excluded-blocks.delete.bulk'), {
        onSuccess: (res) => {
            toastResponse(res, () => {
                selectedItems.value = [];
            });
        },
        onStart: () => {
            isDeletingBlock.value = true;
        },
        onFinish: () => {
            isDeletingBlock.value = false;
        },
        data: {
            block_ids: selectedItems.value,
        },
    });
}
</script>

<template>
    <Head title="Excluded Blocks" />
    <ProjectLayout page-title="Excluded Blocks">
        <div class="animate__animated animate__fadeIn mx-auto max-w-5xl space-y-6">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row">
                <div class="space-y-2">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Excluded Blocks</h2>
                    <p class="max-w-2xl leading-relaxed text-muted-foreground">
                        Define CSS selectors for elements that should be ignored by the translation engine. This is useful for brand names, codes, or specific UI components.
                    </p>
                </div>
                <AddExcludedBlockDialog v-if="canManageContent">
                    <Button class="bg-primary shadow-lg shadow-primary/20 transition-all duration-300 hover:bg-primary/90">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Rule
                    </Button>
                </AddExcludedBlockDialog>
            </div>

            <Card class="overflow-hidden border-none bg-white/80 shadow-sm backdrop-blur-sm">
                <CardHeader class="border-b border-gray-100 bg-gray-50/50 pb-4">
                    <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                        <div class="flex items-center gap-2 text-gray-600">
                            <ShieldAlert class="h-5 w-5" />
                            <span class="font-medium">Active Excluded Blocks</span>
                            <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700">{{ excludedBlocks.total }}</span>
                        </div>

                        <div class="flex w-full items-center gap-2 sm:w-72">
                            <SearchInput v-model="filterForm.q" placeholder="Search selectors..." autocomplete="off" class="flex-1" @keyup="clearFilter" @keyup.enter="runFilter" />
                            <Button v-show="filterForm.q.length" class="h-10 shrink-0 px-3" size="sm" variant="ghost" @click="runFilter"> Search </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <div v-if="selectedItems.length && canManageContent" class="flex animate-in items-center justify-between border-b border-red-100 bg-red-50/50 p-2 px-4 slide-in-from-top-2">
                        <span class="text-sm font-medium text-red-700">{{ selectedItems.length }} item(s) selected</span>
                        <ConfirmAction variant="destructive" description="Delete selected blocks?" :action="bulkDelete" :loading="isDeletingBlock">
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
                                        :model-value="selectedItems.length > 0 && selectedItems.length === excludedBlocks.data?.length"
                                        @update:model-value="(val) => toggleAll(val as boolean, excludedBlocks.data, 'id')"
                                        class="border-gray-300 data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                                    />
                                </TableHead>
                                <TableHead class="font-semibold text-gray-600">Selector</TableHead>
                                <TableHead class="font-semibold text-gray-600">Description</TableHead>
                                <TableHead class="w-[100px] text-right font-semibold text-gray-600">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="block in excludedBlocks.data" :key="`block-${block.id}`" class="group border-gray-100 transition-colors hover:bg-gray-50/50">
                                <TableCell v-if="canManageContent">
                                    <Checkbox
                                        :model-value="selectedItems.includes(block.id)"
                                        @update:model-value="(val) => selectItem(val as boolean, block.id)"
                                        class="border-gray-300 data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                                    />
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <div class="rounded bg-gray-100 p-1.5 text-gray-500">
                                            <Ban class="h-3.5 w-3.5" />
                                        </div>
                                        <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-sm text-pink-600">{{ block.selector }}</code>
                                    </div>
                                </TableCell>
                                <TableCell class="text-gray-600">{{ block.description || '-' }}</TableCell>
                                <TableCell v-if="canManageContent" class="text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <EditExcludedBlockDialog :block="block">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-gray-500 hover:bg-blue-50 hover:text-blue-600">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </EditExcludedBlockDialog>

                                        <ConfirmAction description="Remove block?" variant="destructive" :action="() => deleteBlock(block.id)" :loading="isDeletingBlock">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-gray-500 hover:bg-red-50 hover:text-red-600">
                                                <Trash class="h-4 w-4" />
                                            </Button>
                                        </ConfirmAction>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="!excludedBlocks.data?.length">
                                <TableCell colspan="4" class="h-64 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="mb-3 rounded-full bg-gray-50 p-4">
                                            <ShieldAlert class="h-8 w-8 text-gray-300" />
                                        </div>
                                        <p class="text-lg font-medium text-gray-900">No blocks found</p>
                                        <p class="mt-1 max-w-sm text-sm text-gray-500">Add rules to prevent specific parts of your website from being translated.</p>
                                        <AddExcludedBlockDialog v-if="canManageContent">
                                            <Button variant="link" class="mt-2 text-primary"> Add your first rule </Button>
                                        </AddExcludedBlockDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>

                <div v-if="excludedBlocks.next_page_url || excludedBlocks.prev_page_url" class="border-t border-gray-100 bg-gray-50/30 p-4">
                    <Pagination :links="excludedBlocks.links" />
                </div>
            </Card>
        </div>
    </ProjectLayout>
</template>

<style scoped>
/* Add any scoped styles here */
</style>

<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { SearchInput } from '@/components/ui/search-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { routeWithProject } from '@/lib/helpers';
import { LanguageI, PageI, PaginatedDataI, TranslationUsageI } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

type Props = {
    translations: PaginatedDataI<TranslationUsageI>;
    pages: PageI[];
    languages: LanguageI[];
    filters: {
        q?: string | null;
        page_id?: string | number | null;
        language_id?: string | number | null;
    };
    summary: {
        total: number;
        recently_used: number;
        stale: number;
        never_used: number;
    };
    recentUsageDays: number;
};

const props = defineProps<Props>();

const filterForm = reactive({
    q: props.filters.q ?? '',
    page_id: props.filters.page_id ? String(props.filters.page_id) : 'all',
    language_id: props.filters.language_id ? String(props.filters.language_id) : 'all',
});

const activeFilterCount = computed(() => {
    return [filterForm.q, filterForm.page_id !== 'all', filterForm.language_id !== 'all'].filter(Boolean).length;
});

function applyFilters() {
    const filter: Record<string, string> = {};

    if (filterForm.q.trim()) {
        filter.q = filterForm.q.trim();
    }

    if (filterForm.page_id !== 'all') {
        filter.page_id = filterForm.page_id;
    }

    if (filterForm.language_id !== 'all') {
        filter.language_id = filterForm.language_id;
    }

    router.get(routeWithProject('projects.translation-usage.index'), Object.keys(filter).length > 0 ? { filter } : {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.page_id = 'all';
    filterForm.language_id = 'all';
    applyFilters();
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Never';
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

function formatRelativeDate(value: string | null): string {
    if (!value) {
        return 'Never used';
    }

    const lastUsed = new Date(value).getTime();
    const diffMs = Date.now() - lastUsed;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));

    if (diffHours < 1) {
        return 'Just now';
    }

    if (diffHours < 24) {
        return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
    }

    if (diffDays < 30) {
        return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`;
    }

    const diffMonths = Math.floor(diffDays / 30);
    return `${diffMonths} month${diffMonths === 1 ? '' : 's'} ago`;
}

function usageStatus(value: string | null): { label: string; color: 'secondary' | 'default' | 'outline' | 'destructive' } {
    if (!value) {
        return { label: 'Never Used', color: 'secondary' };
    }

    const diffMs = Date.now() - new Date(value).getTime();
    const diffDays = diffMs / (1000 * 60 * 60 * 24);

    if (diffDays <= props.recentUsageDays) {
        return { label: 'Recently Used', color: 'default' };
    }

    return { label: 'Idle', color: 'outline' };
}
</script>

<template>
    <Head title="Translation Usage" />

    <ProjectLayout page-title="Translation Usage">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="space-y-2">
                <h2 class="text-3xl font-bold tracking-tight text-slate-950">Translation Usage</h2>
                <p class="max-w-3xl text-base leading-8 text-muted-foreground">
                    Review translations that have gone idle or have never been served yet, then jump straight into the page where each one is managed.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card class="border-none shadow-sm">
                    <CardHeader class="pb-2">
                        <CardDescription>Total tracked translations</CardDescription>
                        <CardTitle class="text-2xl">{{ summary.total }}</CardTitle>
                    </CardHeader>
                </Card>

                <Card class="border-none shadow-sm">
                    <CardHeader class="pb-2">
                        <CardDescription>Used in the last {{ recentUsageDays }} days</CardDescription>
                        <CardTitle class="text-2xl text-primary">{{ summary.recently_used }}</CardTitle>
                    </CardHeader>
                </Card>

                <Card class="border-none shadow-sm">
                    <CardHeader class="pb-2">
                        <CardDescription>Idle translations</CardDescription>
                        <CardTitle class="text-2xl text-amber-600">{{ summary.stale }}</CardTitle>
                    </CardHeader>
                </Card>

                <Card class="border-none shadow-sm">
                    <CardHeader class="pb-2">
                        <CardDescription>Never used yet</CardDescription>
                        <CardTitle class="text-2xl text-slate-600">{{ summary.never_used }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <Card class="border-none shadow-sm">
                <CardHeader class="space-y-3">
                    <div>
                        <CardTitle>Translation Activity</CardTitle>
                        <CardDescription class="mt-2"> Filter by page, language, or content to focus on idle translations and entries that have never been used. </CardDescription>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <SearchInput v-model="filterForm.q" placeholder="Search source or translated text..." @keyup.enter="applyFilters" />

                        <Select v-model="filterForm.page_id" @update:model-value="applyFilters">
                            <SelectTrigger>
                                <SelectValue placeholder="Filter by page" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All pages</SelectItem>
                                <SelectItem v-for="page in pages" :key="`page-filter-${page.id}`" :value="String(page.id)">
                                    {{ page.path }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="filterForm.language_id" @update:model-value="applyFilters">
                            <SelectTrigger>
                                <SelectValue placeholder="Filter by language" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All languages</SelectItem>
                                <SelectItem v-for="language in languages" :key="`language-filter-${language.id}`" :value="String(language.id)">
                                    {{ language.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div class="flex gap-2">
                            <Button class="flex-1" @click="applyFilters">Apply</Button>
                            <Button variant="outline" @click="resetFilters">
                                Reset
                                <span v-if="activeFilterCount > 0" class="ml-1">({{ activeFilterCount }})</span>
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="min-w-[280px]">Translation</TableHead>
                                    <TableHead class="min-w-[150px]">Page</TableHead>
                                    <TableHead class="min-w-[120px]">Language</TableHead>
                                    <TableHead class="min-w-[130px]">Usage</TableHead>
                                    <TableHead class="min-w-[150px]">Last Used</TableHead>
                                    <TableHead class="min-w-[120px]">Details</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-for="translation in translations.data" :key="translation.id">
                                    <TableCell class="align-top">
                                        <div class="space-y-2">
                                            <div>
                                                <div class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Source</div>
                                                <p class="line-clamp-2 text-sm text-slate-900">{{ translation.text_preview }}</p>
                                            </div>

                                            <div>
                                                <div class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Translated</div>
                                                <p class="line-clamp-2 text-sm text-slate-700">{{ translation.translated_preview }}</p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell class="align-top">
                                        <div class="space-y-1">
                                            <div class="font-medium text-slate-900">{{ translation.page?.path ?? 'Unknown page' }}</div>
                                            <div class="text-xs text-muted-foreground">{{ translation.page?.origin }}</div>
                                        </div>
                                    </TableCell>

                                    <TableCell class="align-top">
                                        <div class="space-y-2">
                                            <div class="font-medium text-slate-900">{{ translation.language?.name ?? 'Unknown' }}</div>
                                            <div class="flex flex-wrap gap-2">
                                                <Badge variant="outline">{{ translation.language?.iso_2 ?? '--' }}</Badge>
                                                <Badge variant="secondary">{{ translation.quality }}</Badge>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell class="align-top">
                                        <div class="space-y-2">
                                            <Badge :variant="usageStatus(translation.last_used_at).color">
                                                {{ usageStatus(translation.last_used_at).label }}
                                            </Badge>
                                            <div class="text-xs text-muted-foreground">{{ translation.total_words }} words</div>
                                        </div>
                                    </TableCell>

                                    <TableCell class="align-top">
                                        <div class="space-y-1">
                                            <div class="font-medium text-slate-900">{{ formatRelativeDate(translation.last_used_at) }}</div>
                                            <div class="text-xs text-muted-foreground">{{ formatDate(translation.last_used_at) }}</div>
                                        </div>
                                    </TableCell>

                                    <TableCell class="align-top">
                                        <Button v-if="translation.manage_url" variant="outline" size="sm" as-child>
                                            <Link :href="translation.manage_url">Open</Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="translations.data.length < 1">
                                    <TableCell colspan="6" class="py-12 text-center text-sm text-muted-foreground"> No idle or never-used translations matched the current filters. </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div v-if="translations.next_page_url || translations.prev_page_url" class="mt-6 border-t border-slate-100 pt-4">
                        <Pagination :links="translations.links" />
                    </div>
                </CardContent>
            </Card>
        </div>
    </ProjectLayout>
</template>

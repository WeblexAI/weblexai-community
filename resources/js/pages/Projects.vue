<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { SearchInput } from '@/components/ui/search-input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PaginatedDataI, ProjectI } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FileText, FolderGit2, Languages } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ projects: PaginatedDataI<ProjectI> }>();
const user = usePage().props.auth.user;
const searchQuery = ref('');
const filteredProjects = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return query ? props.projects.data.filter((project) => project.name.toLowerCase().includes(query)) : props.projects.data;
});
</script>

<template>
    <Head title="Projects" />
    <AppLayout>
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Projects</h1>
                    <p class="mt-1 text-muted-foreground">Projects assigned to your account.</p>
                </div>
                <SearchInput v-model="searchQuery" placeholder="Search projects..." class="w-full sm:w-64" />
            </div>

            <Card class="overflow-hidden border-none shadow-md">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="pl-6">Project</TableHead>
                                <TableHead>Translations</TableHead>
                                <TableHead>Languages</TableHead>
                                <TableHead>Access</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="project in filteredProjects" :key="project.id" class="cursor-pointer" @click="router.visit(route('projects.overview', project.slug))">
                                <TableCell class="pl-6">
                                    <div class="flex items-center gap-3">
                                        <div class="rounded-lg bg-primary/10 p-2 text-primary">
                                            <FolderGit2 class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <div class="font-semibold">{{ project.name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ project.slug }}</div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <FileText class="h-4 w-4 text-muted-foreground" />
                                        {{ project.translations_sum_total_words ?? 0 }} words
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Languages class="h-4 w-4 text-muted-foreground" />
                                        {{ project.languages_count ?? 0 }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">
                                        {{ project.user_id === user.id ? 'Owner' : 'Member' }}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="filteredProjects.length === 0">
                                <TableCell colspan="4" class="h-48 text-center">
                                    <div class="mx-auto max-w-sm space-y-2">
                                        <div class="font-semibold text-slate-900">No project is assigned to this account</div>
                                        <p class="text-sm leading-6 text-muted-foreground">Ask an administrator to create a project and add you as an owner or member.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

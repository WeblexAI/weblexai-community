<script setup lang="ts">
import NoResults from '@/components/NoResults.vue';
import Pagination from '@/components/Pagination.vue';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { ActivityI, PaginatedDataI } from '@/types';
import { Head } from '@inertiajs/vue3';
import { CheckCheck, FileText, Trash2 } from 'lucide-vue-next';

type Props = {
    activities: PaginatedDataI<ActivityI>;
};

defineProps<Props>();
</script>

<template>
    <Head title="Activity Logs" />
    <ProjectLayout page-title="Activity Logs">
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Recent Activities</CardTitle>
                <CardDescription>Stay updated on all important actions.</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col divide-y divide-gray-200">
                    <NoResults v-if="!activities.data.length" />

                    <div v-for="activity in activities.data" :key="activity.id" class="flex items-start gap-4 py-4" v-else>
                        <div v-if="activity.event === 'updated'" class="rounded-full bg-yellow-100 p-2 text-yellow-600">
                            <FileText class="h-4 w-4" />
                        </div>
                        <div v-else-if="activity.event === 'deleted'" class="rounded-full bg-red-100 p-2 text-red-600">
                            <Trash2 class="h-4 w-4" />
                        </div>
                        <div v-else class="rounded-full bg-primary/10 p-2 text-primary">
                            <CheckCheck class="h-4 w-4" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ activity.description }}</p>
                            <p class="text-xs text-muted-foreground">{{ activity.formated_created_at }}</p>
                        </div>
                    </div>
                </div>
            </CardContent>
            <CardFooter v-if="activities.data.length" class="justify-center">
                <Pagination :links="activities.links" v-show="activities.next_page_url || activities.prev_page_url" />
            </CardFooter>
        </Card>
    </ProjectLayout>
</template>

<style scoped></style>

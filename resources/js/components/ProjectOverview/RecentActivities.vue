<script setup lang="ts">
import OverviewActivityLogSkeleton from '@/components/Skeletons/overviewActivityLogSkeleton.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import useAuthorization from '@/composables/useAuthorization';
import { overviewGetActivities } from '@/lib/api';
import { routeWithProject } from '@/lib/helpers';
import { ActivityI } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Activity, CheckCircle2, HelpCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const { canManageSettings } = useAuthorization();

const data = ref<{ activities: ActivityI[] }>({
    activities: [],
});
const loading = ref(true);

onMounted(async () => {
    loading.value = true;
    data.value = await overviewGetActivities();
    loading.value = false;
});
</script>

<template>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Activity Feed -->
        <Card class="lg:col-span-2">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
                <CardTitle class="flex items-center gap-2 text-lg font-semibold"> <Activity class="h-5 w-5 text-primary" /> Recent Activity </CardTitle>
                <Button v-if="canManageSettings" as-child variant="ghost" size="sm" class="h-8">
                    <Link :href="routeWithProject('projects.activity-logs')">View All</Link>
                </Button>
            </CardHeader>
            <CardContent>
                <OverviewActivityLogSkeleton v-if="loading" />
                <div v-else>
                    <div v-if="data.activities.length > 0" class="relative space-y-6 pl-2">
                        <!-- Vertical Line -->
                        <div class="absolute top-2 bottom-2 left-[11px] w-px bg-gray-200"></div>

                        <div v-for="activity in data.activities.slice(0, 5)" :key="`activity-${activity.id}`" class="relative flex items-start gap-4">
                            <div class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white ring-1 ring-gray-200">
                                <CheckCircle2 class="h-3.5 w-3.5 text-primary" />
                            </div>
                            <div class="flex-1 space-y-1 pt-0.5">
                                <p class="text-sm leading-none font-medium text-gray-900">
                                    {{ activity.description }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ activity.formated_created_at }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-sm text-muted-foreground">No recent activity.</div>
                </div>
            </CardContent>
        </Card>

        <!-- Support Card -->
        <Card class="h-fit border-primary/10 bg-primary/5 shadow-none">
            <CardHeader class="pb-2">
                <CardTitle class="flex items-center gap-2 text-base font-semibold"> <HelpCircle class="h-4 w-4 text-primary" /> Need Help? </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
                <p class="text-muted-foreground">Use GitHub Issues for reproducible bugs and GitHub Discussions for installation or architecture questions.</p>
                <a href="https://github.com/weblexai/weblexai-community/issues" target="_blank" rel="noreferrer" class="block font-medium text-primary hover:underline"> Report an issue </a>
                <a href="https://github.com/weblexai/weblexai-community/discussions" target="_blank" rel="noreferrer" class="block text-xs text-muted-foreground underline hover:text-primary">
                    Open community discussions
                </a>
            </CardContent>
        </Card>
    </div>
</template>

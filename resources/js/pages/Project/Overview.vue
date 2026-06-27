<script setup lang="ts">
import Collaborators from '@/components/ProjectOverview/Collaborators.vue';
import ProjectDetails from '@/components/ProjectOverview/ProjectDetails.vue';
import RecentActivities from '@/components/ProjectOverview/RecentActivities.vue';
import { TooltipProvider } from '@/components/ui/tooltip';
import useAuthorization from '@/composables/useAuthorization';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { Head } from '@inertiajs/vue3';

const { canManageSettings } = useAuthorization();
</script>

<template>
    <Head title="Overview" />
    <ProjectLayout page-title="Project Overview">
        <TooltipProvider>
            <div class="mx-auto max-w-7xl space-y-6">
                <ProjectDetails />

                <Collaborators />
                <RecentActivities v-if="canManageSettings" />
            </div>
        </TooltipProvider>
    </ProjectLayout>
</template>

<style scoped></style>

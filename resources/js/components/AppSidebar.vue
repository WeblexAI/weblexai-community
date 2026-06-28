<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import { Sidebar, SidebarContent, SidebarGroup, SidebarGroupLabel, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import SideBarLink from '@/components/ui/SideBarLink.vue';
import useAuthorization from '@/composables/useAuthorization';
import { routeWithProject } from '@/lib/helpers';
import { Link, usePage } from '@inertiajs/vue3';
import { Activity, ArrowRightLeft, Ban, BookOpen, BookText, Cable, ChartLine, Cog, KeyRound, Languages, LayoutDashboard, List, ScanEye } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();
const { canManageSettings } = useAuthorization();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('projects.overview', $page.props.project.slug)">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SideBarLink title="Projects" :href="route('projects.index')" :icon="List" />
                        <SideBarLink title="Overview" :href="route('projects.overview', page.props.project.slug)" :icon="LayoutDashboard" />
                        <SideBarLink v-if="canManageSettings" title="Project Setup" :href="route('projects.setup', page.props.project.slug)" :icon="Cable" />
                    </SidebarMenuItem>

                    <SidebarGroupLabel class="text-primary">TRANSLATIONS</SidebarGroupLabel>
                    <SidebarMenuItem>
                        <SideBarLink title="Languages" :href="route('projects.languages.index', page.props.project.slug)" :icon="Languages" />
                        <SideBarLink
                            v-if="page.props.project.first_language"
                            title="Pages"
                            :href="routeWithProject('projects.languages.pages.index', { language: page.props.project.first_language.iso_2 })"
                            :icon="BookOpen"
                        />
                        <SideBarLink title="Translation Usage" :href="routeWithProject('projects.translation-usage.index')" :icon="Activity" />
                        <SideBarLink title="Glossary" :href="route('projects.glossaries.index', page.props.project.slug)" :icon="BookText" />
                        <SideBarLink title="Excluded Blocks" :href="routeWithProject('projects.excluded-blocks.index')" :icon="Ban" />
                    </SidebarMenuItem>

                    <SidebarGroupLabel class="text-primary">STATISTICS</SidebarGroupLabel>
                    <SidebarMenuItem>
                        <SideBarLink title="Translation Requests" :href="routeWithProject('projects.translation-requests.index')" :icon="ChartLine" />
                        <SideBarLink title="Page Views" :href="routeWithProject('projects.page-views.index')" :icon="ScanEye" />
                    </SidebarMenuItem>

                    <SidebarGroupLabel v-if="canManageSettings" class="text-primary">SETTINGS</SidebarGroupLabel>
                    <SidebarMenuItem v-if="canManageSettings">
                        <SideBarLink title="Translation Provider" :href="routeWithProject('projects.translation-model.index')" :icon="KeyRound" />
                        <SideBarLink title="Language Switcher" :href="routeWithProject('projects.language-switcher.index')" :icon="ArrowRightLeft" />
                        <SideBarLink title="Project Configuration" :href="routeWithProject('projects.settings')" :icon="Cog" />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <NavFooter />
    </Sidebar>
    <slot />
</template>

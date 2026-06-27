<script setup lang="ts">
import { SidebarMenuButton } from '@/components/ui/sidebar';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    title: string
    href: string,
    icon?: unknown,
}>()
</script>

<template>
    <SidebarMenuButton as-child :is-active="href === $page.props.full_url" :tooltip="title">
        <Link :href="href">
            <component :is="icon" />
            <span>{{ title }}</span>
        </Link>
    </SidebarMenuButton>
</template>

<style scoped>

</style>

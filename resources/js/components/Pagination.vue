<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { PaginationLinkI } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    links: PaginationLinkI[];
}>();

const filteredLinks = computed(() => props.links.filter((link) => link.url !== null));
</script>

<template>
    <div class="flex flex-wrap justify-center gap-1">
        <template v-for="(link, i) in filteredLinks" :key="i">
            <Link :href="link.url ?? ''" preserve-scroll>
                <Button variant="outline" :class="['h-8 min-w-[32px] border-gray-300 px-2 py-1 transition-colors duration-400', link.active ? 'bg-primary text-white' : '']">
                    <template v-if="link.label.includes('Previous')">
                        <ChevronLeft class="h-4 w-4" />
                    </template>
                    <template v-else-if="link.label.includes('Next')">
                        <ChevronRight class="h-4 w-4" />
                    </template>
                    <template v-else>
                        {{ link.label }}
                    </template>
                </Button>
            </Link>
        </template>
    </div>
</template>

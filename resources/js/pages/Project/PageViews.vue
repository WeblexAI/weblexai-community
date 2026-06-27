<script setup lang="ts">
import AttachLanguageDialog from '@/components/language/AttachLanguageDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import useProject from '@/composables/useProject';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import world from '@/lib/world.json';
import { Head, router } from '@inertiajs/vue3';
import { registerMap } from 'echarts';
import { MapChart } from 'echarts/charts';
import { GeoComponent, TooltipComponent, VisualMapComponent } from 'echarts/components';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { House, SpellCheck } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import VChart from 'vue-echarts';

use([TooltipComponent, VisualMapComponent, GeoComponent, MapChart, CanvasRenderer]);

registerMap('world', world as any);

type Props = {
    pageViewsByCountry: { name: string; value: number }[];
    pageViewsByLanguage: { language_id: number; language_name: string; is_added_to_project: boolean; views_count: number }[];
};
const props = defineProps<Props>();

const project = useProject();

const chartOptions = ref({
    tooltip: {
        trigger: 'item',
        formatter: (params: { name: string; value?: number }) => {
            return `${params.name}: ${params.value || 0} visits`;
        },
    },
    visualMap: {
        min: 0,
        max: 200,
        text: ['High', 'Low'],
        realtime: false,
        calculable: true,
        inRange: {
            color: ['#f2e6d5', 'green'],
        },
        show: false,
    },

    geo: {
        map: 'world',
        zoom: 1.2,
        roam: true,
        scaleLimit: {
            min: 1,
            max: 7,
        },
    },

    toolbox: {
        show: true,
        orient: 'vertical',
        left: 'left',
        top: 'top',
        feature: {
            restore: {},
            saveAsImage: {},
        },
    },

    series: [
        {
            name: 'Website Visits',
            type: 'map',
            map: 'world',
            roam: true,
            geoIndex: 0,
            emphasis: {
                label: {
                    show: true,
                },
            },
            data: props.pageViewsByCountry,
        },
    ],
});

const dateFilter = ref('l30');

function filterByDate(selected: string) {
    const params = new URLSearchParams(window.location.search);
    params.set('date', selected);
    const url = `${window.location.pathname}?${params.toString()}`;
    router.visit(url);
}

onMounted(function () {
    const params = new URLSearchParams(window.location.search);
    const date = params.get('date') || null;
    dateFilter.value = date ?? dateFilter.value;
});
</script>

<template>
    <Head title="Page Views" />
    <ProjectLayout page-title="Translation Requests">
        <div class="mb-5 flex justify-end">
            <Select v-model="dateFilter" @update:model-value="(val) => filterByDate(val as string)">
                <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="Select a fruit" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="l30"> Last 30 days </SelectItem>
                    <SelectItem value="l7"> Last 7 days </SelectItem>
                    <SelectItem value="yesterday"> Yesterday </SelectItem>
                    <SelectItem value="today"> Today </SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="grid grid-cols-3 gap-8">
            <Card class="col-span-3 rounded-sm lg:col-span-2">
                <CardContent>
                    <div class="text-xl font-bold text-primary">By Countries</div>
                    <p class="text-faded text-sm">Whenever someone visits your site, Weblex automatically captures their country of origin—no matter which language the page shows.</p>
                    <div class="mt-10 h-[600px]">
                        <VChart class="min-h-[600px] w-full" :option="chartOptions" autoresize />
                    </div>
                </CardContent>
            </Card>
            <Card class="col-span-3 rounded-sm lg:col-span-1">
                <CardContent class="h-[600px]">
                    <div class="text-xl font-bold text-primary">Top 10 Browser languages</div>
                    <p class="text-faded text-sm">Whenever someone visits a page on your site, Weblex automatically captures their browser language, no matter which language the page shows.</p>
                    <div class="mt-3">
                        <ScrollArea class="h-[600px] w-full">
                            <Table>
                                <TableHeader class="cursor-pointer bg-gray-200">
                                    <TableRow class="border-b-white">
                                        <TableHead class="font-bold text-black uppercase">Info</TableHead>
                                        <TableHead class="font-bold text-black uppercase">Language</TableHead>
                                        <TableHead class="font-bold text-black uppercase">Views</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody class="w-full">
                                    <TableRow v-for="viewData in pageViewsByLanguage" :key="viewData.language_id" class="cursor-pointer">
                                        <TableCell class="py-5 text-sm">
                                            <House v-if="viewData.language_id === project.original_language_id" :size="18" class="text-accent" />
                                            <SpellCheck v-else-if="viewData.is_added_to_project" :size="18" class="text-accent" />

                                            <AttachLanguageDialog v-else :language_id="viewData.language_id" :language_name="viewData.language_name">
                                                <Button size="sm" class="rounded-xs" variant="secondary">Add</Button>
                                            </AttachLanguageDialog>
                                        </TableCell>
                                        <TableCell class="py-5 text-sm">{{ viewData.language_name }}</TableCell>
                                        <TableCell>{{ viewData.views_count }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </ScrollArea>
                    </div>
                </CardContent>
            </Card>
        </div>
    </ProjectLayout>
</template>

<style scoped></style>

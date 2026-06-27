<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { DonutChart } from '@/components/ui/chart-donut';
import { LineChart } from '@/components/ui/chart-line';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type LineDatum = {
    date: string;
    [key: string]: string | number;
};

type Props = {
    requestsCount: number;
    donutData: { name: string; total: number; color: string }[];
    lineData: LineDatum[];
    lineColors: string[];
    pagesData: {
        path: string;
        requests_count: number;
        translationRequests: {
            color: number;
            language_name: string;
            count: number;
            percentage: number;
        }[];
    }[];
};

const props = defineProps<Props>();

const lineCategories = Object.keys(props.lineData[0] ?? {}).filter((k) => k !== 'date');

const newlineData = ref<LineDatum[]>(props.lineData);

onMounted(function () {
    newlineData.value = props.lineData;
});

const valueFormatter = function (tick: number | Date) {
    return typeof tick === 'number' ? `${new Intl.NumberFormat('us').format(tick).toString()} requests` : '';
};

const dateFilter = ref('l30');
const lineGroupByModel = ref('day');

function filterByDate(selected: string) {
    const params = new URLSearchParams(window.location.search);
    params.set('date', selected);
    const url = `${window.location.pathname}?${params.toString()}`;
    router.visit(url);
}

function lineGroubBy(selected: 'day' | 'week' | 'month') {
    if (selected !== lineGroupByModel.value) {
        const params = new URLSearchParams(window.location.search);
        params.set('linegby', selected);
        const url = `${window.location.pathname}?${params.toString()}`;
        router.visit(url);
    }
}

onMounted(function () {
    const params = new URLSearchParams(window.location.search);
    const date = params.get('date') || null;
    const lineGBy = params.get('linegby') || null;

    dateFilter.value = date ?? dateFilter.value;
    lineGroupByModel.value = lineGBy ?? lineGroupByModel.value;
});
</script>

<template>
    <Head title="Translation Requests" />
    <ProjectLayout page-title="Translation Requests">
        <TooltipProvider>
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

            <div class="flex flex-col gap-8">
                <div class="grid grid-cols-3 gap-8">
                    <Card class="col-span-1 rounded-sm">
                        <CardContent>
                            <div class="text-xl font-bold text-primary">Distribution by Language</div>
                            <div class="text-faded">Frequency of Translation Requests</div>
                            <div class="mt-10">
                                <DonutChart
                                    class="h-70"
                                    type="pie"
                                    index="name"
                                    :category="'total'"
                                    :data="donutData"
                                    :value-formatter="valueFormatter"
                                    :colors="Object.values(donutData.map((lang) => lang.color))"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="col-span-2 rounded-sm">
                        <CardContent>
                            <div class="flex gap-5">
                                <div>
                                    <div class="text-xl font-bold text-primary">{{ requestsCount }} Translation requests</div>
                                    <div class="text-faded">Requests matching selected filters.</div>
                                </div>
                                <div class="ms-auto">
                                    <div class="flex gap-3">
                                        <Badge
                                            @click="
                                                () => {
                                                    lineGroubBy('day');
                                                }
                                            "
                                            :variant="lineGroupByModel === 'day' ? 'default' : 'muted'"
                                            class="cursor-pointer px-3 py-1.5 font-semibold"
                                            >DAY</Badge
                                        >
                                        <Badge
                                            @click="
                                                () => {
                                                    lineGroubBy('week');
                                                }
                                            "
                                            :variant="lineGroupByModel === 'week' ? 'default' : 'muted'"
                                            class="cursor-pointer px-3 py-1.5 font-semibold"
                                            >WEEK</Badge
                                        >
                                        <Badge
                                            @click="
                                                () => {
                                                    lineGroubBy('month');
                                                }
                                            "
                                            :variant="lineGroupByModel === 'month' ? 'default' : 'muted'"
                                            class="cursor-pointer px-3 py-1.5 font-semibold"
                                            >MONTH</Badge
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="mt-10">
                                <LineChart v-if="newlineData" :data="newlineData" index="date" :categories="lineCategories" :y-formatter="(tick) => `${tick} views`" :colors="lineColors" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card class="rounded-sm">
                    <CardHeader class="text-xl font-semibold text-primary"> Translation requests by page </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader class="cursor-pointer bg-gray-200">
                                <TableRow class="border-b-white">
                                    <TableHead class="min-w-md font-bold text-black uppercase">PAGE</TableHead>
                                    <TableHead class="font-bold text-black uppercase">TRANSLATION REQUESTS</TableHead>
                                    <TableHead class="font-bold text-black uppercase">LANGUAGE DISTRIBUTION</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="page in pagesData" :key="page.path" class="cursor-pointer">
                                    <TableCell class="py-5 text-sm">
                                        {{ page.path }}
                                    </TableCell>
                                    <TableCell class="py-5 text-sm">{{ page.requests_count }}</TableCell>
                                    <TableCell>
                                        <div class="flex h-[25px] w-[350px]">
                                            <Tooltip v-for="(req, index) in page.translationRequests" :key="`tr-${req.language_name}`">
                                                <TooltipTrigger
                                                    class="cursor-pointer"
                                                    :class="{
                                                        'rounded-s-sm': index == 0,
                                                        'rounded-e-sm': index == page.translationRequests.length - 1,
                                                    }"
                                                    :style="{ backgroundColor: req.color, width: req.percentage + '%' }"
                                                />
                                                <TooltipContent>
                                                    <div class="flex gap-4">
                                                        <div>{{ req.language_name }}</div>
                                                        <div>{{ req.count }} translations</div>
                                                        <div>{{ req.percentage }}%</div>
                                                    </div>
                                                </TooltipContent>
                                            </Tooltip>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </TooltipProvider>
    </ProjectLayout>
</template>

<style scoped></style>

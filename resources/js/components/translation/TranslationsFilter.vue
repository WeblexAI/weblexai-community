<script setup lang="ts">
import InputText from '@/components/InputText.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useFilterQuery } from '@/inertia-essentials/filter-sqb';
import { ChevronDown } from 'lucide-vue-next';

type Props = {
    form: any;
};

const props = defineProps<Props>();

const { runFilter } = useFilterQuery({
    form: props.form,
    buildQuery: (qBuilder) => {
        if (props.form.q) qBuilder.filter('q', props.form.q);
        if (props.form.quality.length) qBuilder.filter('quality', props.form.quality.join(','));
        if (props.form.status.length) qBuilder.filter('status', props.form.status.join(','));
        if (props.form.is_reviewed) qBuilder.filter('is_reviewed', props.form.is_reviewed);
    },
});

function filterQuality(quality: 'automatic' | 'manual', value: boolean) {
    const index = props.form.quality.indexOf(quality);
    if (value && index < 0) {
        props.form.quality.push(quality);
    } else if (!value && index > -1) {
        props.form.quality.splice(index, 1);
    }
    runFilter();
}

function filterStatus(status: 'active' | 'inactive', value: boolean) {
    const index = props.form.status.indexOf(status);
    if (value && index < 0) {
        props.form.status.push(status);
    } else if (!value && index > -1) {
        props.form.status.splice(index, 1);
    }
    runFilter();
}
</script>

<template>
    <div class="ms-auto flex items-start gap-5">
        <div class="relative flex flex-col">
            <InputText placeholder="Search..." container-class="gap-0 w-100" :form="form" model="q" autocomplete="off" class="pe-[72px]" />
            <Button v-show="form.q.length" class="absolute right-0 ms-auto mt-2 h-6 py-0 text-xs" size="sm" variant="secondary" @click="runFilter"> Search </Button>
        </div>
        <Popover>
            <PopoverTrigger>
                <div class="flex cursor-pointer items-center gap-1 rounded-sm p-2.5 hover:bg-gray-200">
                    <span class="text-sm">Quality</span>
                    <ChevronDown class="ms-1" :size="17" />
                </div>
            </PopoverTrigger>
            <PopoverContent class="flex max-w-[150px] flex-col gap-1 rounded-none px-0 py-2 text-sm shadow-lg">
                <label class="flex cursor-pointer items-center gap-3 px-3 py-1 hover:bg-gray-100">
                    <Checkbox
                        :model-value="form.quality.includes('automatic')"
                        @update:model-value="
                            (val) => {
                                filterQuality('automatic', val as boolean);
                            }
                        "
                    />
                    Automatic
                </label>
                <label class="flex cursor-pointer items-center gap-3 px-3 py-1 hover:bg-gray-100">
                    <Checkbox
                        :model-value="form.quality.includes('manual')"
                        @update:model-value="
                            (val) => {
                                filterQuality('manual', val as boolean);
                            }
                        "
                    />
                    Manual
                </label>
            </PopoverContent>
        </Popover>
        <Popover>
            <PopoverTrigger>
                <div class="flex cursor-pointer items-center gap-1 rounded-sm p-2.5 hover:bg-gray-200">
                    <span class="text-sm">Status</span>
                    <ChevronDown class="ms-1" :size="17" />
                </div>
            </PopoverTrigger>
            <PopoverContent class="flex max-w-[150px] flex-col gap-1 rounded-none px-0 py-2 text-sm shadow-lg">
                <label class="flex cursor-pointer items-center gap-3 px-3 py-1 hover:bg-gray-100">
                    <Checkbox
                        :model-value="form.status.includes('active')"
                        @update:model-value="
                            (val) => {
                                filterStatus('active', val as boolean);
                            }
                        "
                    />
                    Active
                </label>
                <label class="flex cursor-pointer items-center gap-3 px-3 py-1 hover:bg-gray-100">
                    <Checkbox
                        :model-value="form.status.includes('inactive')"
                        @update:model-value="
                            (val) => {
                                filterStatus('inactive', val as boolean);
                            }
                        "
                    />
                    Inactive
                </label>
            </PopoverContent>
        </Popover>
    </div>
</template>

<style scoped></style>

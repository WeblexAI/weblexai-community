<script setup lang="ts">
import InputText from '@/components/InputText.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { ExcludedBlockI } from '@/types';
import { useForm } from '@inertiajs/vue3';

type Props = {
    block: ExcludedBlockI;
};

const props = defineProps<Props>();

const form = useForm({
    selector: props.block.selector,
    description: props.block.description,
});

function submit() {
    form.put(routeWithProject('projects.excluded-blocks.update', { block: props.block.id }), {
        onSuccess: (res) => toastResponse(res),
        preserveScroll: true,
    });
}
</script>

<template>
    <Dialog>
        <DialogTrigger>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <DialogTitle>Edit Excluded Block</DialogTitle>
            <DialogDescription></DialogDescription>
            <form @submit.prevent="submit" class="flex flex-col gap-5">
                <InputText label="Blocks to exclude" :form="form" model="selector" required />
                <InputText label="Description" :form="form" model="description" />
                <div class="flex justify-center">
                    <Button :processing="form.processing" class="min-w-[50%]">Save Changes</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>

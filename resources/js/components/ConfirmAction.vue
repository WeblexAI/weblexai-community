<script setup lang="ts">
import { Button, type ButtonVariants } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { randomString } from '@/lib/helpers';
import { cn } from '@/lib/utils';
import { CircleCheckBig, Info, Trash } from 'lucide-vue-next';

const props = defineProps<{
    action?: any;
    loading?: boolean;
    variant?: ButtonVariants['variant'];
    description?: string | null | unknown;
    className?: string;
    closeAfterAction?: boolean;
}>();

const closeDialogId = randomString(10);

async function handleAction() {
    if (props.action) await props.action();

    if (props.closeAfterAction) {
        const closeDialogBtn = document.getElementById(closeDialogId);
        if (closeDialogBtn) {
            setTimeout(() => {
                closeDialogBtn.click();
            }, 100);
        }
    }
}
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>

        <DialogContent class="gap-2 sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle></DialogTitle>
            </DialogHeader>
            <DialogDescription />

            <div v-if="variant === 'success'" class="mx-auto rounded-full bg-green-100 p-3">
                <CircleCheckBig class="text-primary" :size="27" />
            </div>

            <div v-else-if="variant === 'destructive'" class="mx-auto rounded-full bg-red-100 p-3">
                <Trash class="text-destructive" :size="27" />
            </div>

            <div v-else class="mx-auto rounded-full bg-blue-100 p-3">
                <Info class="text-secondary" :size="27" />
            </div>

            <div class="text-center text-lg font-bold">Confirm Action</div>

            <div :class="cn('text-faded max-h-[350px] overflow-y-auto text-center', props.className)">
                {{ props.description || 'Confirm this action ?' }}
            </div>

            <DialogFooter class="mt-4 grid grid-cols-2 gap-3">
                <DialogClose as-child>
                    <button :id="closeDialogId" class="h-9 transform cursor-pointer rounded border border-gray-300 px-7 text-sm transition-transform duration-300 hover:scale-102" :disabled="loading">
                        Close
                    </button>
                </DialogClose>

                <Button type="button" size="sm" :variant="variant || 'default'" @click="handleAction" :processing="loading" class="h-9 px-7"> Yes </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

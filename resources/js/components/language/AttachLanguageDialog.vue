<script setup lang="ts">
import LanguageSelector from '@/components/LanguageSelector.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { LanguageI } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const closeDialog = ref<HTMLButtonElement | null>(null);
const form = useForm<{
    language_id: number | null;
    is_public: boolean;
    should_display_automatics: boolean;
}>({
    language_id: null,
    is_public: true,
    should_display_automatics: true,
});

const props = defineProps<{
    languages?: LanguageI[];
    language_id?: number;
    language_name?: string;
}>();

function submit() {
    form.post(routeWithProject('projects.languages.index'), {
        onSuccess: (res) => {
            toastResponse(res, () => {
                closeDialog.value?.click();
                form.reset();
            });
        },
    });
}

onMounted(function () {
    form.language_id = props.language_id ?? form.language_id;
});
</script>

<template>
    <Dialog>
        <DialogTrigger>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <DialogTitle>
                <span v-if="language_name && language_id">Attach {{ language_name?.toUpperCase() }} to your project</span>
                <span v-else>Attach language to your project</span>
            </DialogTitle>
            <DialogDescription>
                <!--                Not in the list? Create a local variation of a language (like British English or Mexican), or a complete custom language.-->
            </DialogDescription>
            <form @submit.prevent="submit" class="flex flex-col gap-5">
                <LanguageSelector v-if="languages && !language_id" :languages="languages" :form="form" model="language_id" label="Language" searchable />

                <div v-show="form.language_id" class="flex items-center">
                    <div class="flex items-center space-x-2">
                        <Checkbox v-model="form.is_public" id="is_public" />
                        <Label for="is_public" class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"> Make public </Label>
                    </div>
                    <Tooltip>
                        <TooltipTrigger class="ms-auto">
                            <Info class="cursor-pointer" :size="18" />
                        </TooltipTrigger>
                        <TooltipContent>
                            Turn this off to make the language private, <br />
                            so it can only be accessed by adding <br /><b>?wsai-prv=1</b> to the end of the URL. <br />
                            This will hide it from regular visitors.
                        </TooltipContent>
                    </Tooltip>
                </div>
                <div v-show="form.language_id" class="flex items-center">
                    <div class="flex items-center space-x-2">
                        <Checkbox v-model="form.should_display_automatics" id="display_automatic_trans" />
                        <Label for="display_automatic_trans" class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"> Display automatic translations </Label>
                    </div>
                    <Tooltip>
                        <TooltipTrigger class="ms-auto">
                            <Info class="cursor-pointer" :size="18" />
                        </TooltipTrigger>
                        <TooltipContent>
                            Turn this off if you want to display the <br />
                            original sentence instead of automatic <br />
                            translations on your website. When disabled, <br />
                            only manual translations will be shown.
                        </TooltipContent>
                    </Tooltip>
                </div>
                <div class="flex justify-center">
                    <Button :processing="form.processing" class="min-w-[50%]">Add language</Button>
                </div>
            </form>
            <DialogClose as-child>
                <button ref="closeDialog" class="hidden"></button>
            </DialogClose>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>

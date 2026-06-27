<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { LanguageI, PageI } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Props = {
    targetLanguage: LanguageI;
    originalLanguage: LanguageI;
    page: PageI;
};

const props = defineProps<Props>();
const excelFileInput = ref<{ inputRef?: HTMLInputElement } | null>(null);

const form = useForm({
    file: null,
    page_id: props.page.id,
    target_lang_id: props.targetLanguage.id,
});

function importTranslations() {
    form.post(routeWithProject('projects.translations.import'), {
        onSuccess: (res) => {
            toastResponse(res, () => {
                form.reset();
                if (excelFileInput.value?.inputRef) {
                    excelFileInput.value.inputRef.value = '';
                }
            });
        },
    });
}
</script>

<template>
    <Dialog>
        <DialogTrigger>
            <slot />
        </DialogTrigger>
        <DialogContent @interact-outside="(event) => event.preventDefault()" hide-x>
            <DialogHeader>
                <DialogTitle>
                    <div class="mb-4 text-center text-2xl">Import Translations</div>
                    <div class="font-semibold text-accent">
                        <div>Page: {{ page.path }}</div>
                        <div>Language pairs: {{ originalLanguage.name }} to {{ targetLanguage.name }}</div>
                    </div>
                </DialogTitle>
            </DialogHeader>

            <div class="grid gap-2">
                <Label>Select file</Label>
                <Input
                    @input="form.file = $event.target.files[0]"
                    type="file"
                    accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    ref="excelFileInput"
                />
                <InputError :message="form.errors.file" />
                <div class="flex justify-between gap-5">
                    <small class="font-bold text-secondary">Only Excel files are supported. Please ensure your file headers match the sample file.</small>
                    <Button variant="secondary" size="sm" as="a" :href="routeWithProject('projects.translations.import.sample')"> Download sample file </Button>
                </div>
            </div>

            <DialogFooter class="justify-start sm:justify-start">
                <DialogClose :disabled="form.processing">
                    <Button variant="destructive" :disabled="form.processing" class="px-10">Close</Button>
                </DialogClose>
                <Button @click="importTranslations" :processing="form.processing" class="px-10">Import</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>

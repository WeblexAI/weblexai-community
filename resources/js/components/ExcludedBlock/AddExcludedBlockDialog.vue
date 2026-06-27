<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { useForm } from '@inertiajs/vue3';
import { Code, FileText, Plus, ShieldAlert } from 'lucide-vue-next';
import { ref } from 'vue';

const open = ref(false);

const form = useForm({
    selector: '',
    description: '',
});

function submit() {
    form.post(routeWithProject('projects.excluded-blocks.create'), {
        onSuccess: (res) => {
            toastResponse(res, () => {
                form.reset();
                open.value = false;
            });
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 p-2">
                        <ShieldAlert class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <DialogTitle class="text-xl">Add Exclusion Rule</DialogTitle>
                        <DialogDescription class="mt-1"> Define a CSS selector to exclude from translation. </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="submit" class="mt-4 space-y-5">
                <!-- Selector Input -->
                <div class="space-y-2">
                    <Label for="selector" class="flex items-center gap-2 text-sm font-medium">
                        <Code class="h-4 w-4 text-gray-500" />
                        CSS Selector
                        <span class="text-destructive">*</span>
                    </Label>
                    <div class="relative">
                        <Input id="selector" v-model="form.selector" placeholder="e.g. .brand-name or #logo" class="font-mono text-sm" required :disabled="form.processing" />
                    </div>
                    <p class="text-xs text-muted-foreground">Must start with <code class="rounded bg-gray-100 px-1">#</code> for IDs or <code class="rounded bg-gray-100 px-1">.</code> for classes.</p>
                    <InputError :message="form.errors.selector" />
                </div>

                <!-- Description Input -->
                <div class="space-y-2">
                    <Label for="description" class="flex items-center gap-2 text-sm font-medium">
                        <FileText class="h-4 w-4 text-gray-500" />
                        Description
                        <span class="text-xs font-normal text-muted-foreground">(Optional)</span>
                    </Label>
                    <Textarea id="description" v-model="form.description" placeholder="Brief note about this exclusion..." class="min-h-[80px] resize-none" :disabled="form.processing" />
                    <p class="text-xs text-muted-foreground">Help your team understand why this element is excluded.</p>
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-2">
                    <Button type="button" variant="outline" @click="open = false" :disabled="form.processing"> Cancel </Button>
                    <Button type="submit" :processing="form.processing" class="bg-primary shadow-md hover:bg-primary/90">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Rule
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>

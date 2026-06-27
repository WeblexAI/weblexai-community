<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { IconInput } from '@/components/ui/icon-input';
import { Label } from '@/components/ui/label';
import { randomString, toastResponse } from '@/lib/helpers';
import { User } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Mail, Save, User as UserIcon } from 'lucide-vue-next';

type Props = {
    user: User;
};

const props = defineProps<Props>();
const form = useForm({
    name: props.user.name,
    email: props.user.email,
});
const closeBtnId = randomString();

async function submit() {
    form.put(route('profile'), {
        preserveScroll: true,
        onSuccess: (res) => {
            toastResponse(res, () => {
                const closeBtn = document.getElementById(closeBtnId);
                closeBtn?.click();
            });
        },
    });
}
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="gap-0 overflow-hidden border-none p-0 shadow-2xl sm:max-w-[450px]">
            <div class="bg-white p-6 pt-8">
                <DialogHeader class="mb-6">
                    <DialogTitle class="text-2xl font-bold tracking-tight text-gray-900">Edit Profile </DialogTitle>
                    <DialogDescription class="text-gray-500"> Update your personal information. Click save when you're done. </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Name Field -->
                    <div class="space-y-2">
                        <Label for="name" class="text-sm font-medium text-gray-700">Full Name</Label>
                        <IconInput id="name" v-model="form.name" class="bg-gray-50/50" placeholder="Your full name">
                            <template #icon>
                                <UserIcon class="h-4 w-4" />
                            </template>
                        </IconInput>
                        <span v-if="form.errors.name" class="text-xs font-medium text-destructive">{{ form.errors.name }}</span>
                    </div>

                    <!-- Email Field -->
                    <div class="space-y-2">
                        <Label for="email" class="text-sm font-medium text-gray-700">Email Address</Label>
                        <IconInput id="email" type="email" v-model="form.email" class="bg-gray-50/50" placeholder="name@example.com">
                            <template #icon>
                                <Mail class="h-4 w-4" />
                            </template>
                        </IconInput>
                        <span v-if="form.errors.email" class="text-xs font-medium text-destructive">{{ form.errors.email }}</span>
                    </div>
                </form>
            </div>

            <DialogFooter class="flex flex-row justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:justify-end">
                <DialogClose as-child>
                    <Button :id="closeBtnId" variant="outline" class="border-gray-200 bg-white text-gray-700 hover:bg-gray-100">Cancel</Button>
                </DialogClose>
                <Button type="submit" @click="submit" :disabled="form.processing" class="bg-primary text-white shadow-sm hover:bg-primary/90">
                    <Save class="mr-2 h-4 w-4" v-if="!form.processing" />
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Save Changes</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

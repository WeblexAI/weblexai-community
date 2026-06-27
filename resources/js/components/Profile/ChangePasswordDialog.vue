<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { IconInput } from '@/components/ui/icon-input';
import { Label } from '@/components/ui/label';
import { randomString, toastResponse } from '@/lib/helpers';
import { useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, KeyRound, Lock, Save } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const closeBtnId = randomString();

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

async function submit() {
    form.put(route('profile.password'), {
        preserveScroll: true,
        onSuccess: (res) => {
            toastResponse(res, () => {
                const closeBtn = document.getElementById(closeBtnId);
                closeBtn?.click();
                form.reset();
                showCurrentPassword.value = false;
                showNewPassword.value = false;
                showConfirmPassword.value = false;
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
                    <DialogTitle class="text-2xl font-bold tracking-tight text-gray-900"> Change Password </DialogTitle>
                    <DialogDescription class="text-gray-500"> Confirm your current password and choose a strong replacement. </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Current Password -->
                    <div class="space-y-2">
                        <Label for="current_password" class="text-sm font-medium text-gray-700">Current Password</Label>
                        <IconInput
                            id="current_password"
                            :type="showCurrentPassword ? 'text' : 'password'"
                            v-model="form.current_password"
                            placeholder="Enter current password"
                            required
                            class="bg-gray-50/50"
                        >
                            <template #icon>
                                <Lock class="h-4 w-4" />
                            </template>
                            <template #trailing>
                                <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <Eye v-if="!showCurrentPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </template>
                        </IconInput>
                        <span v-if="form.errors.current_password" class="text-xs font-medium text-destructive">{{ form.errors.current_password }}</span>
                    </div>

                    <!-- New Password -->
                    <div class="space-y-2">
                        <Label for="password" class="text-sm font-medium text-gray-700">New Password</Label>
                        <IconInput id="password" :type="showNewPassword ? 'text' : 'password'" v-model="form.password" placeholder="Enter new password" required class="bg-gray-50/50">
                            <template #icon>
                                <KeyRound class="h-4 w-4" />
                            </template>
                            <template #trailing>
                                <button type="button" @click="showNewPassword = !showNewPassword" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <Eye v-if="!showNewPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </template>
                        </IconInput>
                        <span v-if="form.errors.password" class="text-xs font-medium text-destructive">{{ form.errors.password }}</span>
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-2">
                        <Label for="password_confirmation" class="text-sm font-medium text-gray-700">Confirm Password</Label>
                        <IconInput
                            id="password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            v-model="form.password_confirmation"
                            placeholder="Confirm new password"
                            required
                            class="bg-gray-50/50"
                        >
                            <template #icon>
                                <KeyRound class="h-4 w-4" />
                            </template>
                            <template #trailing>
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <Eye v-if="!showConfirmPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </template>
                        </IconInput>
                        <span v-if="form.errors.password_confirmation" class="text-xs font-medium text-destructive">{{ form.errors.password_confirmation }}</span>
                    </div>
                </form>
            </div>

            <DialogFooter class="flex flex-row justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:justify-end">
                <DialogClose as-child>
                    <Button :id="closeBtnId" variant="outline" class="border-gray-200 bg-white text-gray-700 hover:bg-gray-100">Cancel</Button>
                </DialogClose>
                <Button type="submit" @click="submit" :disabled="form.processing" class="bg-primary text-white shadow-sm hover:bg-primary/90">
                    <Save class="mr-2 h-4 w-4" v-if="!form.processing" />
                    <span v-if="form.processing">Updating...</span>
                    <span v-else>Update Password</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

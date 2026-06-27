<script setup lang="ts">
import ChangePasswordDialog from '@/components/Profile/ChangePasswordDialog.vue';
import UpdateProfileDialog from '@/components/Profile/UpdateProfileDialog.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { randomString } from '@/lib/helpers';
import type { User } from '@/types';
import { Head } from '@inertiajs/vue3';
import { AlertTriangle, Mail, Shield, User as UserIcon } from 'lucide-vue-next';

defineProps<{
    user: User;
}>();
</script>

<template>
    <Head title="Profile" />
    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <Alert v-if="user.force_password_change" class="border-amber-300 bg-amber-50 text-amber-950">
                <AlertTriangle class="h-5 w-5 text-amber-600" />
                <AlertTitle>Password change required</AlertTitle>
                <AlertDescription> You are using a temporary password. Change it before accessing your projects. </AlertDescription>
            </Alert>

            <Card>
                <CardContent class="flex flex-col items-center gap-5 p-8 sm:flex-row">
                    <Avatar class="h-24 w-24">
                        <AvatarFallback>{{ user.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
                    </Avatar>
                    <div class="flex-1 text-center sm:text-left">
                        <h1 class="text-2xl font-bold">{{ user.name }}</h1>
                        <p class="mt-1 flex items-center justify-center gap-2 text-muted-foreground sm:justify-start">
                            <Mail class="h-4 w-4" />
                            {{ user.email }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <UserIcon class="h-5 w-5 text-primary" />
                            Account
                        </CardTitle>
                        <CardDescription>Update your name and email address.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <UpdateProfileDialog :user="user" :key="randomString()">
                            <Button variant="outline">Edit Profile</Button>
                        </UpdateProfileDialog>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Shield class="h-5 w-5 text-primary" />
                            Security
                        </CardTitle>
                        <CardDescription>Change your current password.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ChangePasswordDialog>
                            <Button variant="outline">Change Password</Button>
                        </ChangePasswordDialog>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

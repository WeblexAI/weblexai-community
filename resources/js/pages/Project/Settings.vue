<script setup lang="ts">
import ConfirmAction from '@/components/ConfirmAction.vue';
import InputText from '@/components/InputText.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import useProject from '@/composables/useProject';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Settings, Zap } from 'lucide-vue-next';

const project = useProject();
const form = useForm({
    name: project.name,
    should_display_automatics: project.should_display_automatics,
});

function updateProject() {
    form.patch(routeWithProject('projects.update'), {
        preserveScroll: true,
        onSuccess: (response) => toastResponse(response),
    });
}
</script>

<template>
    <Head title="Project Settings" />
    <ProjectLayout page-title="Project Settings">
        <div class="mx-auto max-w-4xl space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Settings class="h-5 w-5 text-primary" />
                        General
                    </CardTitle>
                    <CardDescription>Project naming and content behavior.</CardDescription>
                </CardHeader>
                <CardContent>
                    <InputText label="Project Name" required :form="form" model="name" placeholder="My website" class="max-w-md" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Zap class="h-5 w-5 text-primary" />
                        Automatic Translation
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between gap-6 rounded-lg border p-4">
                        <div>
                            <Label>Display automatic translations</Label>
                            <p class="mt-1 text-sm text-muted-foreground">Disable this when translations must be reviewed before they appear.</p>
                        </div>
                        <Switch v-model="form.should_display_automatics" />
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end">
                <ConfirmAction variant="success" description="Save project settings?" :action="updateProject" :loading="form.processing">
                    <Button :processing="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </ConfirmAction>
            </div>
        </div>
    </ProjectLayout>
</template>

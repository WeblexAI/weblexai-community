<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import useProject from '@/composables/useProject';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Clipboard, Code2, KeyRound, Lightbulb, Rocket } from 'lucide-vue-next';
import { ref } from 'vue';

const project = useProject();
const page = usePage();
const props = defineProps<{ apiKey: string | null }>();
const copied = ref<string | null>(null);

const installationCode = `<link rel="stylesheet" href="${page.props.cdn_url}/wlai/weblexai.css">
<script src="${page.props.cdn_url}/wlai/weblexai.min.js"><\/script>
<script>
  WeblexAI.init('${props.apiKey ?? 'YOUR_PROJECT_API_KEY'}');
<\/script>`;

async function copy(value: string, label: string) {
    await navigator.clipboard.writeText(value);
    copied.value = label;
    window.setTimeout(() => (copied.value = null), 1600);
}
</script>

<template>
    <Head title="Project Setup" />
    <ProjectLayout page-title="Project Setup">
        <div class="mx-auto max-w-5xl space-y-8">
            <Alert :class="project.is_integrated ? 'border-emerald-300 bg-emerald-50' : 'border-amber-300 bg-amber-50'">
                <component :is="project.is_integrated ? CheckCircle2 : AlertCircle" class="h-5 w-5" :class="project.is_integrated ? 'text-emerald-600' : 'text-amber-600'" />
                <AlertTitle>{{ project.is_integrated ? 'Integration active' : 'Integration not detected' }}</AlertTitle>
                <AlertDescription>
                    {{ project.is_integrated ? 'WeblexAI is receiving content from this website.' : 'Complete the steps below, then visit your website to start the integration.' }}
                </AlertDescription>
            </Alert>

            <Card class="overflow-hidden shadow-sm">
                <CardHeader>
                    <div class="flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-lg font-bold text-emerald-600">1</span>
                        <div>
                            <CardTitle class="flex items-center gap-2"><KeyRound class="h-5 w-5 text-emerald-600" /> Project API key</CardTitle>
                            <CardDescription class="mt-1">This key identifies your project when the browser SDK starts.</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="apiKey" class="flex items-center gap-3 rounded-xl bg-slate-950 p-4 text-slate-100">
                        <code class="min-w-0 flex-1 overflow-x-auto text-sm text-amber-300">{{ apiKey }}</code>
                        <Button variant="secondary" @click="copy(apiKey, 'key')"><Clipboard class="mr-2 h-4 w-4" />{{ copied === 'key' ? 'Copied' : 'Copy' }}</Button>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">Ask an administrator to generate a project API key.</p>
                </CardContent>
            </Card>

            <Card class="shadow-sm">
                <CardHeader>
                    <div class="flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-lg font-bold text-emerald-600">2</span>
                        <div>
                            <CardTitle class="flex items-center gap-2"><Code2 class="h-5 w-5 text-emerald-600" /> Install the browser SDK</CardTitle>
                            <CardDescription class="mt-1">Add this snippet before the closing head tag on every page.</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="relative">
                        <pre class="overflow-x-auto rounded-xl bg-slate-950 p-6 text-sm leading-6 text-slate-100"><code>{{ installationCode }}</code></pre>
                        <Button class="absolute right-4 top-4" @click="copy(installationCode, 'code')"><Clipboard class="mr-2 h-4 w-4" />{{ copied === 'code' ? 'Copied' : 'Copy code' }}</Button>
                    </div>
                    <Alert class="border-blue-200 bg-blue-50">
                        <Lightbulb class="h-5 w-5 text-blue-600" />
                        <AlertDescription>Add the snippet to your main layout so it loads consistently across the website.</AlertDescription>
                    </Alert>
                </CardContent>
            </Card>

            <Card class="shadow-sm">
                <CardHeader>
                    <div class="flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-lg font-bold text-emerald-600">3</span>
                        <div>
                            <CardTitle class="flex items-center gap-2"><Rocket class="h-5 w-5 text-emerald-600" /> Verify the integration</CardTitle>
                            <CardDescription class="mt-1">Finish the project configuration and send the first request.</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <ol class="space-y-4">
                        <li v-for="(step, index) in [
                            'Ask an administrator to add your website origin to the project.',
                            'Add at least one target language.',
                            'Open your website and navigate through a translated page.',
                            'Return here to confirm that the integration is active.',
                        ]" :key="step" class="flex items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-700">{{ index + 1 }}</span>
                            <span class="pt-0.5 text-sm text-slate-700">{{ step }}</span>
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </div>
    </ProjectLayout>
</template>

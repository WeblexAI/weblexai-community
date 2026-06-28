<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import useProject from '@/composables/useProject';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { AlertCircle, Check, CheckCircle2, Clipboard, Code2, Globe2, KeyRound, Languages, Lightbulb, Rocket, Settings2, ShieldCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const project = useProject();
const page = usePage();
const props = defineProps<{ apiKey: string | null }>();
const copied = ref<string | null>(null);

const installationCode = `<link rel="stylesheet" href="${page.props.cdn_url}/wlai/weblexai.css">
<script src="${page.props.cdn_url}/wlai/weblexai.min.js"><\/script>
<script>
  WeblexAI.init('${props.apiKey ?? 'YOUR_PROJECT_API_KEY'}');
<\/script>`;

const acceptedOriginsCount = computed(() => project.accepted_origins?.length ?? 0);
const hasTargetLanguage = computed(() => Boolean(project.first_language ?? project.firstLanguage));
const launchItems = computed(() => [
    {
        label: 'Provider credential',
        description: project.provider_credential ? `${project.provider_credential.provider_label} is assigned.` : 'Assign a provider credential in the admin panel.',
        complete: Boolean(project.provider_credential),
        icon: Settings2,
    },
    {
        label: 'Project API key',
        description: props.apiKey ? 'The browser SDK can identify this project.' : 'Generate or rotate the project API key from the admin panel.',
        complete: Boolean(props.apiKey),
        icon: KeyRound,
    },
    {
        label: 'Accepted origin',
        description:
            acceptedOriginsCount.value > 0 ? `${acceptedOriginsCount.value} accepted origin${acceptedOriginsCount.value === 1 ? '' : 's'} configured.` : 'Add the exact website origin before testing.',
        complete: acceptedOriginsCount.value > 0,
        icon: ShieldCheck,
    },
    {
        label: 'Target language',
        description: hasTargetLanguage.value ? 'At least one target language is available.' : 'Add a target language before loading the SDK.',
        complete: hasTargetLanguage.value,
        icon: Languages,
    },
    {
        label: 'Website detected',
        description: project.is_integrated ? 'WeblexAI has received content from the website.' : 'Visit the website after installing the snippet.',
        complete: project.is_integrated,
        icon: Globe2,
    },
]);
const completedLaunchItems = computed(() => launchItems.value.filter((item) => item.complete).length);
const launchReady = computed(() => completedLaunchItems.value >= launchItems.value.length - 1);

async function copy(value: string, label: string) {
    await navigator.clipboard.writeText(value);
    copied.value = label;
    window.setTimeout(() => (copied.value = null), 1600);
}
</script>

<template>
    <Head title="Project Setup" />
    <ProjectLayout page-title="Project Setup">
        <div class="mx-auto max-w-6xl space-y-7">
            <Card class="overflow-hidden border-slate-200 bg-slate-950 text-white shadow-lg">
                <CardHeader class="gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="max-w-2xl">
                        <CardDescription class="font-semibold tracking-[0.18em] text-emerald-300 uppercase">Launch checklist</CardDescription>
                        <CardTitle class="mt-3 text-2xl font-semibold text-white">Get this project ready for the first translated page.</CardTitle>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Complete the required setup items, install the snippet, then visit the accepted website origin to verify that content is flowing into WeblexAI.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-right">
                        <div class="text-3xl font-semibold">{{ completedLaunchItems }}/{{ launchItems.length }}</div>
                        <div class="text-xs font-medium tracking-wide text-slate-300 uppercase">checks complete</div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-3 md:grid-cols-5">
                        <div
                            v-for="item in launchItems"
                            :key="item.label"
                            class="rounded-2xl border p-4"
                            :class="item.complete ? 'border-emerald-400/25 bg-emerald-400/10' : 'border-white/10 bg-white/[0.03]'"
                        >
                            <div class="flex items-center justify-between">
                                <component :is="item.icon" class="h-5 w-5" :class="item.complete ? 'text-emerald-300' : 'text-slate-400'" />
                                <span class="flex h-6 w-6 items-center justify-center rounded-full" :class="item.complete ? 'bg-emerald-400 text-slate-950' : 'bg-white/10 text-slate-400'">
                                    <Check v-if="item.complete" class="h-4 w-4" />
                                </span>
                            </div>
                            <div class="mt-4 text-sm font-semibold">{{ item.label }}</div>
                            <p class="mt-1 text-xs leading-5 text-slate-300">{{ item.description }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Alert :class="project.is_integrated ? 'border-emerald-300 bg-emerald-50' : 'border-amber-300 bg-amber-50'">
                <component :is="project.is_integrated ? CheckCircle2 : AlertCircle" class="h-5 w-5" :class="project.is_integrated ? 'text-emerald-600' : 'text-amber-600'" />
                <AlertTitle>{{ project.is_integrated ? 'Integration active' : launchReady ? 'Ready to test' : 'Setup incomplete' }}</AlertTitle>
                <AlertDescription>
                    {{
                        project.is_integrated
                            ? 'WeblexAI is receiving content from this website.'
                            : launchReady
                              ? 'Install the snippet and open the website from an accepted origin.'
                              : 'Complete the missing checklist items before testing the browser SDK.'
                    }}
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
                    <p v-else class="text-sm text-muted-foreground">Generate or rotate the project API key from the admin project details page before sharing this setup with a website developer.</p>
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
                        <Button class="absolute top-4 right-4" @click="copy(installationCode, 'code')"><Clipboard class="mr-2 h-4 w-4" />{{ copied === 'code' ? 'Copied' : 'Copy code' }}</Button>
                    </div>
                    <Alert class="border-blue-200 bg-blue-50">
                        <Lightbulb class="h-5 w-5 text-blue-600" />
                        <AlertDescription>Add the snippet to your main layout so it loads consistently across the website. The request origin must exactly match one accepted origin.</AlertDescription>
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
                        <li
                            v-for="(step, index) in [
                                'Confirm the provider credential, API key, accepted origin, and target language checks are complete.',
                                'Add the SDK snippet to the website layout that renders every page.',
                                'Open the website from the accepted origin and navigate through a translated page.',
                                'Return here to confirm that the integration is active.',
                            ]"
                            :key="step"
                            class="flex items-start gap-3"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-700">{{ index + 1 }}</span>
                            <span class="pt-0.5 text-sm text-slate-700">{{ step }}</span>
                        </li>
                    </ol>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <Badge variant="outline">Exact origin required</Badge>
                        <Badge variant="outline">Provider credential required</Badge>
                        <Badge variant="outline">Target language required</Badge>
                    </div>
                </CardContent>
            </Card>
        </div>
    </ProjectLayout>
</template>

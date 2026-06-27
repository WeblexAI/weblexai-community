<script setup lang="ts">
import ConfirmAction from '@/components/ConfirmAction.vue';
import InputText from '@/components/InputText.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Slider } from '@/components/ui/slider';
import { Switch } from '@/components/ui/switch';
import ProjectLayout from '@/layouts/ProjectLayout.vue';
import { routeWithProject, toastResponse } from '@/lib/helpers';
import { LanguageSwitcherConfig } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LayoutTemplate, Monitor, MousePointerClick, Move, Palette, Save, Smartphone } from 'lucide-vue-next';
import { ref } from 'vue';

type Props = {
    config: LanguageSwitcherConfig;
};
const props = defineProps<Props>();

const switcherSize = ref([props.config.size]);

const form = useForm({
    target_parent_selector: props.config.target_parent_selector,
    should_display_name: props.config.should_display_name,
    should_display_full_name: props.config.should_display_full_name,
    should_display_flag: props.config.should_display_flag,
    size: props.config.size,
    should_open_on_hover: props.config.should_open_on_hover,
    should_close_on_outside_click: props.config.should_close_on_outside_click,
    should_show_by_device: props.config.should_show_by_device,
    preferred_device: props.config.preferred_device,
    device_pixel_breakpoint: props.config.device_pixel_breakpoint,
});

function submit() {
    form.size = switcherSize.value[0];
    form.put(routeWithProject('projects.language-switcher.update'), {
        onSuccess(res) {
            toastResponse(res);
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Language Switcher" />
    <ProjectLayout page-title="Language Switcher">
        <div class="animate__animated animate__fadeIn mx-auto max-w-4xl space-y-6">
            <div class="space-y-6">
                <!-- Positioning Section -->
                <Card class="border-none bg-white/80 shadow-sm backdrop-blur-sm">
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-purple-50 p-2">
                                <Move class="h-5 w-5 text-purple-600" />
                            </div>
                            <div>
                                <CardTitle class="text-lg">Positioning</CardTitle>
                                <CardDescription>Control where the language switcher appears on your site. </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="rounded-lg border border-purple-100 bg-purple-50/50 p-4 text-sm text-purple-800">
                            Your switcher is currently located in the default position. You may instead modify the CSS selectors to directly control where your switcher is positioned.
                            <div class="mt-1 italic opacity-80">The switcher will be placed inside the element matching this selector.</div>
                        </div>
                        <InputText v-model="form.target_parent_selector" label="Target Parent Selector" placeholder="e.g. #header-menu or .navbar-right" container-class="max-w-md" />
                    </CardContent>
                </Card>

                <!-- Appearance Section -->
                <Card class="border-none bg-white/80 shadow-sm backdrop-blur-sm">
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-blue-50 p-2">
                                <Palette class="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <CardTitle class="text-lg">Appearance</CardTitle>
                                <CardDescription>Customize how the language switcher looks.</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Display Name -->
                        <div class="flex items-center justify-between rounded-lg p-3 transition-colors hover:bg-gray-50">
                            <div class="space-y-0.5">
                                <Label class="text-base">Display Language Name</Label>
                                <p class="text-sm text-muted-foreground">Show the name of the language (e.g. English).</p>
                                <p v-if="!form.should_display_flag" class="mt-1 text-xs font-medium text-destructive">Cannot be disabled while flag is hidden.</p>
                            </div>
                            <Switch
                                v-model="form.should_display_name"
                                @update:model-value="
                                    (value) => {
                                        form.should_display_flag = !value ? true : form.should_display_flag;
                                    }
                                "
                            />
                        </div>

                        <!-- Display Full Name -->
                        <div class="flex items-center justify-between rounded-lg p-3 transition-colors hover:bg-gray-50">
                            <div class="space-y-0.5">
                                <Label class="text-base">Display Full Name</Label>
                                <p class="text-sm text-muted-foreground">Show full name (English) instead of code (EN).</p>
                            </div>
                            <Switch v-model="form.should_display_full_name" />
                        </div>

                        <!-- Display Flag -->
                        <div class="flex items-center justify-between rounded-lg p-3 transition-colors hover:bg-gray-50">
                            <div class="space-y-0.5">
                                <Label class="text-base">Display Flag</Label>
                                <p class="text-sm text-muted-foreground">Show the country flag next to the language.</p>
                                <p v-if="!form.should_display_name" class="mt-1 text-xs font-medium text-destructive">Cannot be disabled while name is hidden.</p>
                            </div>
                            <Switch
                                v-model="form.should_display_flag"
                                @update:model-value="
                                    (value) => {
                                        form.should_display_name = !value ? true : form.should_display_name;
                                    }
                                "
                            />
                        </div>

                        <!-- Size Slider -->
                        <div class="rounded-lg p-3 transition-colors hover:bg-gray-50">
                            <Label class="mb-4 block text-base">Switcher Size</Label>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-muted-foreground">Small</span>
                                <Slider v-model="switcherSize" :max="100" :step="1" class="flex-1" />
                                <span class="text-sm text-muted-foreground">Large</span>
                            </div>
                            <p class="mt-2 text-right text-xs text-muted-foreground">{{ switcherSize[0] }}%</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Behavior Section -->
                <Card class="border-none bg-white/80 shadow-sm backdrop-blur-sm">
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-orange-50 p-2">
                                <MousePointerClick class="h-5 w-5 text-orange-600" />
                            </div>
                            <div>
                                <CardTitle class="text-lg">Behavior</CardTitle>
                                <CardDescription>Configure how users interact with the switcher.</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Open on Hover -->
                        <div class="flex items-center justify-between rounded-lg p-3 transition-colors hover:bg-gray-50">
                            <div class="space-y-0.5">
                                <Label class="text-base">Open on Hover</Label>
                                <p class="text-sm text-muted-foreground">Open dropdown when hovering instead of clicking.</p>
                            </div>
                            <Switch v-model="form.should_open_on_hover" />
                        </div>

                        <!-- Close on Outside Click -->
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-2"
                        >
                            <div v-if="!form.should_open_on_hover" class="flex items-center justify-between rounded-lg p-3 transition-colors hover:bg-gray-50">
                                <div class="space-y-0.5">
                                    <Label class="text-base">Close on Outside Click</Label>
                                    <p class="text-sm text-muted-foreground">Close dropdown when clicking elsewhere on the page.</p>
                                </div>
                                <Switch v-model="form.should_close_on_outside_click" />
                            </div>
                        </Transition>

                        <!-- Device Visibility -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between rounded-lg p-3 transition-colors hover:bg-gray-50">
                                <div class="space-y-0.5">
                                    <Label class="text-base">Device Specific Visibility</Label>
                                    <p class="text-sm text-muted-foreground">Show switcher only on specific screen sizes.</p>
                                </div>
                                <Switch v-model="form.should_show_by_device" />
                            </div>

                            <Transition
                                enter-active-class="transition-all duration-300 ease-out"
                                enter-from-class="opacity-0 max-h-0"
                                enter-to-class="opacity-100 max-h-96"
                                leave-active-class="transition-all duration-200 ease-in"
                                leave-from-class="opacity-100 max-h-96"
                                leave-to-class="opacity-0 max-h-0"
                            >
                                <div v-if="form.should_show_by_device" class="ml-3 space-y-4 border-l-2 border-gray-100 pl-4">
                                    <div class="grid gap-2">
                                        <Label>Display on</Label>
                                        <Select v-model="form.preferred_device">
                                            <SelectTrigger class="w-full md:w-[240px]">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="desktop">
                                                    <div class="flex items-center gap-2"><Monitor class="h-4 w-4" /> Desktop</div>
                                                </SelectItem>
                                                <SelectItem value="mobile">
                                                    <div class="flex items-center gap-2"><Smartphone class="h-4 w-4" /> Mobile</div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="grid max-w-md gap-2">
                                        <Label>Breakpoint (pixels)</Label>
                                        <div class="relative">
                                            <Input type="number" v-model="form.device_pixel_breakpoint" class="pl-9" />
                                            <LayoutTemplate class="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                        </div>
                                        <p class="text-xs text-muted-foreground">Screen width threshold for visibility.</p>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </CardContent>
                </Card>

                <!-- Save Action -->
                <div class="flex justify-end pt-4">
                    <ConfirmAction variant="success" description="Save configuration changes?" :action="submit" :loading="form.processing">
                        <Button
                            type="submit"
                            :processing="form.processing"
                            class="min-w-[150px] bg-gradient-to-r from-primary to-green-600 shadow-lg transition-all duration-300 hover:from-primary/90 hover:to-green-600/90 hover:shadow-primary/25"
                        >
                            <Save class="mr-2 h-4 w-4" />
                            Save Changes
                        </Button>
                    </ConfirmAction>
                </div>
            </div>
        </div>
    </ProjectLayout>
</template>

<style scoped></style>

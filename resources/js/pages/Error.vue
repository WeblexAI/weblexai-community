<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, FileQuestion, Home, ServerCrash, ShieldAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    status: number;
    message?: string;
}>();

const title = computed(() => {
    switch (props.status) {
        case 404:
            return 'Page Not Found';
        case 503:
            return 'Service Unavailable';
        case 500:
            return 'Server Error';
        case 403:
            return 'Forbidden';
        case 405:
            return 'Method Not Allowed';
        default:
            return 'Error';
    }
});

const description = computed(() => {
    if (props.message) return props.message;
    switch (props.status) {
        case 404:
            return "Sorry, we couldn't find the page you're looking for. It might have been moved or deleted.";
        case 503:
            return 'We are currently performing some maintenance. Please check back soon.';
        case 500:
            return 'Whoops, something went wrong on our servers.';
        case 403:
            return 'Sorry, you are not authorized to access this page.';
        case 405:
            return 'Sorry, the request method is not supported for this resource.';
        default:
            return 'An unexpected error occurred.';
    }
});

const icon = computed(() => {
    switch (props.status) {
        case 404:
            return FileQuestion;
        case 503:
            return ServerCrash;
        case 500:
            return ServerCrash;
        case 403:
            return ShieldAlert;
        case 405:
            return ShieldAlert;
        default:
            return ServerCrash;
    }
});

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 p-4">
        <!-- Background decoration -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -top-96 -left-96 flex h-[800px] w-[800px] rounded-full bg-primary/5 opacity-50 blur-3xl"></div>
            <div class="absolute -right-96 -bottom-96 flex h-[800px] w-[800px] rounded-full bg-blue-500/5 opacity-50 blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-lg">
            <!-- Modern glass card -->
            <div class="relative space-y-8 overflow-hidden rounded-3xl border border-white/20 bg-white/80 p-8 text-center shadow-xl backdrop-blur-xl md:p-12">
                <!-- Top accent line -->
                <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-transparent via-primary/50 to-transparent"></div>

                <!-- Icon circle -->
                <div class="group mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full border border-gray-100 bg-gray-50 shadow-inner">
                    <component :is="icon" class="h-10 w-10 text-gray-400 transition-all duration-300 group-hover:scale-110 group-hover:text-primary" stroke-width="1.5" />
                </div>

                <!-- Content -->
                <div class="space-y-3">
                    <h1 class="bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-7xl font-bold tracking-tighter text-transparent select-none">
                        {{ status }}
                    </h1>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        {{ title }}
                    </h2>
                    <p class="mx-auto max-w-sm text-lg leading-relaxed text-gray-500">
                        {{ description }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex flex-col items-center justify-center gap-3 pt-4 sm:flex-row">
                    <Button variant="outline" class="w-full gap-2 sm:w-auto" @click="goBack">
                        <ArrowLeft class="h-4 w-4" />
                        Go Back
                    </Button>

                    <Link href="/" class="w-full sm:w-auto">
                        <Button class="w-full gap-2 shadow-lg shadow-primary/25 transition-shadow hover:shadow-primary/40">
                            <Home class="h-4 w-4" />
                            Back to Home
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Footer text -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-400">Need help? Check the community support policy in the project repository.</p>
            </div>
        </div>
    </div>
</template>

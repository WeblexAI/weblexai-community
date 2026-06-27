<script setup lang="ts">
import AuthField from '@/components/auth/AuthField.vue';
import AuthPanel from '@/components/auth/AuthPanel.vue';
import AuthSubmit from '@/components/auth/AuthSubmit.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';
import type { AppPageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const showPassword = ref(false);

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const page = usePage<AppPageProps>();
const authNotice = computed(() => page.props.error);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// --- Translation feed animation ---
const pairs = [
    { from: 'Get started', to: 'Commencer', lang: 'French' },
    { from: 'Dashboard', to: 'Instrumententafel', lang: 'German' },
    { from: 'Features', to: 'Funciones', lang: 'Spanish' },
    { from: 'Contact us', to: 'Contacte-nos', lang: 'Portuguese' },
    { from: 'Documentation', to: 'Documentazione', lang: 'Italian' },
];
const activeIdx = ref(0);
let feedTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    feedTimer = setInterval(() => {
        activeIdx.value = (activeIdx.value + 1) % pairs.length;
    }, 2800);
});

onUnmounted(() => {
    if (feedTimer) clearInterval(feedTimer);
});
</script>

<template>
    <Head title="Sign in" />

    <div class="flex min-h-screen w-full">
        <Toaster position="top-right" richColors />

        <!-- Left: Brand panel -->
        <AuthPanel label="Translation platform">
            <!-- Statement -->
            <h2 class="mb-12 max-w-lg text-[clamp(1.8rem,3vw,2.6rem)] leading-[1.2] font-light text-gray-900">
                Your site speaks every language.<br />
                <span class="text-gray-300">Manage it from here.</span>
            </h2>

            <!-- Live translation feed -->
            <div class="relative mb-12 h-6 overflow-hidden">
                <Transition name="feed" mode="out-in">
                    <div :key="activeIdx" class="absolute inset-0 flex items-center gap-3 font-mono text-[13px]">
                        <span class="text-gray-400">{{ pairs[activeIdx].from }}</span>
                        <span class="text-[#34a85a]">&rarr;</span>
                        <span class="text-gray-700">{{ pairs[activeIdx].to }}</span>
                        <span class="ml-1 text-[10px] tracking-widest text-gray-300 uppercase">{{ pairs[activeIdx].lang }}</span>
                    </div>
                </Transition>
            </div>

            <!-- Stats -->
            <div class="flex items-center gap-8">
                <div>
                    <div class="font-mono text-lg font-semibold text-gray-900">40+</div>
                    <div class="mt-0.5 text-[10px] tracking-wide text-gray-400">Languages</div>
                </div>
                <div class="h-6 w-px bg-gray-200"></div>
                <div>
                    <div class="font-mono text-lg font-semibold text-gray-900">&lt;120ms</div>
                    <div class="mt-0.5 text-[10px] tracking-wide text-gray-400">Cache delivery</div>
                </div>
                <div class="h-6 w-px bg-gray-200"></div>
                <div>
                    <div class="font-mono text-lg font-semibold text-gray-900">5 min</div>
                    <div class="mt-0.5 text-[10px] tracking-wide text-gray-400">Setup time</div>
                </div>
            </div>
        </AuthPanel>

        <!-- Right: Form -->
        <div class="flex min-h-screen w-full flex-col bg-white lg:w-[40%]">
            <!-- Logo -->
            <div class="px-10 pt-10">
                <Link href="/">
                    <img src="/images/log-ltr-dark.png" alt="WeblexAI" class="h-6 w-auto opacity-80" />
                </Link>
            </div>

            <!-- Form -->
            <div class="flex flex-1 items-center justify-center px-10">
                <div class="form-enter w-full max-w-[340px]">
                    <h1 class="mb-1 text-[22px] font-semibold tracking-tight text-gray-900">Sign in</h1>
                    <p class="mb-8 text-[13px] text-gray-400">Enter your credentials to continue.</p>

                    <!-- Status -->
                    <div v-if="status" class="mb-6 rounded-lg border border-[#34a85a]/15 bg-[#34a85a]/[0.04] px-4 py-3 font-mono text-[13px] text-[#34a85a]">
                        {{ status }}
                    </div>

                    <div v-if="authNotice" class="mb-6 rounded-lg border border-red-200 bg-red-50/80 px-4 py-3 text-[13px] leading-relaxed text-red-700">
                        {{ authNotice }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <AuthField
                            id="email"
                            label="Email"
                            type="email"
                            v-model="form.email"
                            :error="form.errors.email"
                            placeholder="you@company.com"
                            :required="true"
                            :autofocus="true"
                            autocomplete="username"
                        />

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label for="password" class="block font-mono text-[10px] tracking-[0.12em] text-gray-400 uppercase"> Password </label>
                            </div>
                            <div class="relative">
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                    class="auth-input h-11 w-full rounded-lg border border-gray-200 bg-gray-50/80 px-4 pr-11 text-[14px] text-gray-900 transition-all duration-200 outline-none placeholder:text-gray-300 hover:border-gray-300 focus:-translate-y-px focus:border-[#34a85a] focus:bg-white focus:ring-[3px] focus:ring-[#34a85a]/[0.06]"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 transition-colors hover:text-gray-600"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                >
                                    <EyeOff v-if="showPassword" :size="16" />
                                    <Eye v-else :size="16" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-1.5 text-[12px] text-red-500">{{ form.errors.password }}</p>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <Checkbox id="remember" v-model:checked="form.remember" />
                            <Label for="remember" class="text-[13px] font-normal text-gray-500">Remember me</Label>
                        </div>

                        <AuthSubmit :processing="form.processing" text="Sign in" loading-text="Signing in..." />
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-10 pb-8">
                <p class="font-mono text-[11px] text-gray-300">&copy; {{ new Date().getFullYear() }} WeblexAI</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.form-enter {
    animation: formIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes formIn {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.feed-enter-active,
.feed-leave-active {
    transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}

.feed-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.feed-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>

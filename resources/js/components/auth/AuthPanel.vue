<script setup lang="ts">
defineProps<{
    label: string;
}>();

const langs = [
    { code: 'FR', name: 'French', top: '12%', left: '8%', delay: '0s', dur: '5s' },
    { code: 'DE', name: 'German', top: '8%', left: '52%', delay: '0.8s', dur: '4.5s' },
    { code: 'ES', name: 'Spanish', top: '24%', left: '68%', delay: '1.4s', dur: '5.5s' },
    { code: 'PT', name: 'Portuguese', top: '38%', left: '14%', delay: '0.4s', dur: '4.8s' },
    { code: 'ZH', name: 'Chinese', top: '18%', left: '34%', delay: '2.0s', dur: '5.2s' },
    { code: 'JA', name: 'Japanese', top: '42%', left: '56%', delay: '1.0s', dur: '4.6s' },
    { code: 'IT', name: 'Italian', top: '30%', left: '82%', delay: '1.8s', dur: '5.4s' },
    { code: 'KO', name: 'Korean', top: '6%', left: '78%', delay: '0.6s', dur: '4.4s' },
    { code: 'AR', name: 'Arabic', top: '48%', left: '38%', delay: '2.2s', dur: '5.0s' },
    { code: 'NL', name: 'Dutch', top: '34%', left: '4%', delay: '1.6s', dur: '4.7s' },
];
</script>

<template>
    <div class="auth-panel-bg relative hidden overflow-hidden lg:flex lg:w-[60%]">
        <!-- Soft gradient blobs -->
        <div
            class="pointer-events-none absolute top-[10%] left-[20%] h-[450px] w-[450px] rounded-full opacity-60"
            style="background: radial-gradient(circle, rgba(52, 168, 90, 0.07) 0%, transparent 65%)"
        ></div>
        <div
            class="pointer-events-none absolute right-[10%] bottom-[5%] h-[350px] w-[350px] rounded-full opacity-50"
            style="background: radial-gradient(circle, rgba(52, 168, 90, 0.05) 0%, transparent 60%)"
        ></div>
        <div
            class="pointer-events-none absolute top-[40%] left-[50%] h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full"
            style="background: radial-gradient(circle, rgba(52, 168, 90, 0.04) 0%, rgba(167, 210, 130, 0.02) 40%, transparent 65%)"
        ></div>

        <!-- Floating language pills -->
        <div
            v-for="lang in langs"
            :key="lang.code"
            class="lang-float pointer-events-none absolute"
            :style="{
                top: lang.top,
                left: lang.left,
                animationDelay: lang.delay,
                animationDuration: lang.dur,
            }"
        >
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-gray-100/80 bg-white/80 px-2.5 py-1 text-[11px] font-medium text-gray-400 shadow-[0_1px_4px_rgba(0,0,0,0.04)] backdrop-blur-sm"
            >
                <span class="h-1 w-1 rounded-full bg-[#34a85a]/50"></span>
                {{ lang.code }}
            </span>
        </div>

        <!-- Vertical accent line -->
        <div class="absolute top-12 bottom-12 left-14 w-px bg-gradient-to-b from-transparent via-[#34a85a]/15 to-transparent"></div>

        <!-- Content -->
        <div class="panel-enter relative z-10 flex w-full flex-col justify-end p-14 pl-20">
            <!-- Label -->
            <div class="mb-10 flex items-center gap-3">
                <span class="h-px w-8 bg-[#34a85a]"></span>
                <span class="font-mono text-[10px] tracking-[0.2em] text-[#34a85a] uppercase">{{ label }}</span>
            </div>

            <slot />
        </div>
    </div>
</template>

<style scoped>
.auth-panel-bg {
    background: radial-gradient(ellipse at 25% 75%, rgba(52, 168, 90, 0.04) 0%, transparent 50%), radial-gradient(ellipse at 75% 25%, rgba(52, 168, 90, 0.03) 0%, transparent 50%), #f7f8f6;
}

.lang-float {
    animation: langFloat 5s ease-in-out infinite;
}

@keyframes langFloat {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.panel-enter {
    animation: panelIn 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.15s both;
}

@keyframes panelIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>

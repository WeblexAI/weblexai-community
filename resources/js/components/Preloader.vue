<script setup lang="ts">
// Preloader component
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-white/80 backdrop-blur-sm transition-opacity duration-300">
        <div class="relative flex flex-col items-center">
            <!-- Icon with pulse animation -->
            <div class="relative">
                <div class="absolute inset-0 animate-ping rounded-full bg-emerald-500/20"></div>
                <img src="/images/preloader-icon.png" alt="Loading..." class="relative z-10 h-20 w-20 animate-pulse rounded-full object-contain mix-blend-multiply" />
            </div>

            <!-- Loading Text -->
            <div class="mt-4 animate-bounce text-sm font-medium text-emerald-600">Loading...</div>
        </div>
    </div>
</template>

<style scoped>
/* Additional custom styles if needed */
</style>

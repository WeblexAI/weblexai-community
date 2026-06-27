import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts', 'resources/css/install.css', 'resources/css/filament/admin/theme.css'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vue-vendor': ['vue', '@inertiajs/vue3'],
                    charts: ['echarts', 'vue-echarts', '@unovis/ts', '@unovis/vue'],
                    'ui-libs': ['reka-ui', 'lucide-vue-next', '@vueuse/core'],
                },
            },
        },
        chunkSizeWarningLimit: 1000,
        minify: 'esbuild',
        sourcemap: false,
    },
    server: {
        hmr: {
            overlay: true,
        },
    },
});

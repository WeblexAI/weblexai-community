import vue from "@vitejs/plugin-vue";
import path from "node:path";
import { defineConfig } from "vite";

export default defineConfig(() => {
    const minify = process.env.SDK_MINIFY === "true";

    return {
        plugins: [vue()],
        publicDir: false,
        build: {
            lib: {
                entry: path.resolve(__dirname, "resources/sdk/index.ts"),
                name: "WeblexAI",
                formats: ["iife"],
                fileName: () => minify ? "weblexai.min.js" : "weblexai.js",
            },
            outDir: path.resolve(__dirname, "public/wlai"),
            emptyOutDir: false,
            minify: minify ? "esbuild" : false,
            sourcemap: false,
            rollupOptions: {
                output: {
                    assetFileNames: (asset) => asset.name?.endsWith(".css")
                        ? "weblexai.css"
                        : "[name][extname]",
                },
            },
        },
    };
});

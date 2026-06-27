import vue from "@vitejs/plugin-vue";
import { defineConfig } from "vitest/config";

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: "jsdom",
        globals: true,
        setupFiles: ["./tests/sdk/setup.ts"],
        include: ["tests/sdk/**/*.test.ts"],
    },
});

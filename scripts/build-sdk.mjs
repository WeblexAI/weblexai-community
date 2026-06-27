import { spawnSync } from "node:child_process";

function build(minify) {
    const result = spawnSync(
        process.execPath,
        ["node_modules/vite/bin/vite.js", "build", "--config", "vite.sdk.config.ts"],
        {
            env: { ...process.env, SDK_MINIFY: String(minify) },
            stdio: "inherit",
        },
    );

    if (result.status !== 0) {
        process.exit(result.status ?? 1);
    }
}

build(false);
build(true);

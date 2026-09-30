# Vue Example

Load the SDK from a small component mounted in your app layout.

```vue
<script setup lang="ts">
import { onMounted } from 'vue';

declare global {
    interface Window {
        WeblexAI?: {
            init: (projectKey: string) => Promise<void>;
        };
    }
}

onMounted(() => {
    const baseUrl = import.meta.env.VITE_WEBLEXAI_URL?.replace(/\/+$/, '');
    const projectKey = import.meta.env.VITE_WEBLEXAI_PROJECT_KEY;

    if (!baseUrl || !projectKey || window.WeblexAI) {
        return;
    }

    if (document.querySelector('script[data-weblexai-sdk]')) {
        return;
    }

    const stylesheet = document.createElement('link');
    stylesheet.rel = 'stylesheet';
    stylesheet.href = `${baseUrl}/wlai/weblexai.css`;
    document.head.appendChild(stylesheet);

    const script = document.createElement('script');
    script.dataset.weblexaiSdk = 'true';
    script.src = `${baseUrl}/wlai/weblexai.min.js`;
    script.onload = () => {
        if (!window.WeblexAI) {
            console.error('WeblexAI SDK loaded without exposing the WeblexAI global.');
            return;
        }

        void window.WeblexAI.init(projectKey).catch((error) => {
            console.error('WeblexAI initialization failed:', error);
        });
    };
    script.onerror = () => {
        console.error('WeblexAI SDK could not be loaded.');
    };
    document.head.appendChild(script);
});
</script>

<template>
    <span hidden />
</template>
```

Environment:

This example assumes a Vite-based Vue app, which provides `import.meta.env`.

```text
VITE_WEBLEXAI_URL=http://localhost:8787
VITE_WEBLEXAI_PROJECT_KEY=your-project-api-key
```

Add the Vue app origin, for example `http://localhost:5173`, as an accepted origin on the WeblexAI project.

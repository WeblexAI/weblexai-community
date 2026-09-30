# React Example

Load the SDK once from your app shell.

```tsx
import { useEffect } from 'react';

declare global {
    interface Window {
        WeblexAI?: {
            init: (projectKey: string) => Promise<void>;
        };
    }
}

export function WeblexAiLoader() {
    useEffect(() => {
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
    }, []);

    return null;
}
```

Environment:

This example assumes a Vite-based React app, which provides `import.meta.env`.

```text
VITE_WEBLEXAI_URL=http://localhost:8787
VITE_WEBLEXAI_PROJECT_KEY=your-project-api-key
```

Add the React app origin, for example `http://localhost:5173`, as an accepted origin on the WeblexAI project.

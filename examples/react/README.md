# React Example

Load the SDK once from your app shell.

```tsx
import { useEffect } from 'react';

declare global {
    interface Window {
        WeblexAI?: {
            init: (projectKey: string) => void;
        };
    }
}

export function WeblexAiLoader() {
    useEffect(() => {
        const baseUrl = import.meta.env.VITE_WEBLEXAI_URL;
        const projectKey = import.meta.env.VITE_WEBLEXAI_PROJECT_KEY;

        if (!baseUrl || !projectKey || window.WeblexAI) {
            return;
        }

        const stylesheet = document.createElement('link');
        stylesheet.rel = 'stylesheet';
        stylesheet.href = `${baseUrl}/wlai/weblexai.css`;
        document.head.appendChild(stylesheet);

        const script = document.createElement('script');
        script.src = `${baseUrl}/wlai/weblexai.min.js`;
        script.onload = () => window.WeblexAI?.init(projectKey);
        document.head.appendChild(script);
    }, []);

    return null;
}
```

Environment:

```text
VITE_WEBLEXAI_URL=http://localhost:8787
VITE_WEBLEXAI_PROJECT_KEY=your-project-api-key
```

Add the React app origin, for example `http://localhost:5173`, as an accepted origin on the WeblexAI project.

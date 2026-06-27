import { ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

const storageKey = 'appearance';
const appearance = ref<Appearance>(resolveStoredAppearance());

export function initializeTheme() {
    if (typeof document !== 'undefined') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const shouldUseDark = appearance.value === 'dark' || (appearance.value === 'system' && prefersDark);

        document.documentElement.classList.toggle('dark', shouldUseDark);
    }
}

export function useAppearance() {
    function updateAppearance(value: Appearance) {
        appearance.value = value;
        localStorage.setItem(storageKey, value);
        initializeTheme();
    }

    return {
        appearance,
        updateAppearance,
    };
}

function resolveStoredAppearance(): Appearance {
    if (typeof localStorage === 'undefined') {
        return 'system';
    }

    const stored = localStorage.getItem(storageKey);

    return stored === 'light' || stored === 'dark' || stored === 'system' ? stored : 'system';
}

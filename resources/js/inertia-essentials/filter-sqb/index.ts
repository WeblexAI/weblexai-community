import { router } from '@inertiajs/vue3';
import { query } from '@vortechron/query-builder-ts';
import { onBeforeMount, ref } from 'vue';

export function useFilterQuery(options: { form: any; routeName?: string; preserveKeys?: string[]; buildQuery?: (queryBuilder: any) => any; paginationKey?: string }) {
    const filteredQuery = ref<string | null>(null);
    const filteredItems = ref<Record<string, any>>({});
    const formKeys = Object.keys(options.form.data() ?? []);

    const runFilter = () => {
        const rawUrl = options.routeName ? route(options.routeName) : window.location.href;
        const url = new URL(rawUrl);

        const qBuilder = query();
        if (options.buildQuery) {
            options.buildQuery(qBuilder);
        }
        const built = qBuilder.build().replace('/', '');
        filteredQuery.value = built === '/' ? null : built;

        if (filteredQuery.value) {
            const newParams = new URLSearchParams(filteredQuery.value);
            const newParamsArr = Array.from(newParams.keys()).map((val) => cleanFilterKey(val));

            for (const key of url.searchParams.keys()) {
                const rawKey = cleanFilterKey(key);
                if (formKeys.includes(rawKey) && !newParamsArr.includes(rawKey)) {
                    url.searchParams.delete(key);
                }

                if (options.paginationKey && rawKey == options.paginationKey) {
                    url.searchParams.delete(key);
                }
            }

            for (const [key, value] of newParams.entries()) {
                url.searchParams.set(key, value);
            }
        } else {
            for (const key of url.searchParams.keys()) {
                const rawKey = cleanFilterKey(key);
                if (formKeys.includes(rawKey)) {
                    url.searchParams.delete(key);
                }
            }
        }

        router.visit(url.pathname + '?' + url.searchParams.toString(), {
            preserveScroll: true,
            preserveState: true,
            only: options.preserveKeys ?? [],
            onStart: () => (options.form.processing = true),
            onFinish: () => (options.form.processing = false),
        });
    };

    onBeforeMount(() => {
        const urlParams = new URLSearchParams(window.location.search);

        formKeys.forEach((key) => {
            const param = urlParams.get(`filter[${key}]`);
            if (param !== null) {
                const defaultVal = options.form[key];
                let filterValue: unknown;

                if (Array.isArray(defaultVal)) {
                    filterValue = param.split(',');
                } else if (typeof defaultVal === 'number') {
                    filterValue = Number(param);
                } else {
                    filterValue = param;
                }
                options.form[key] = filterValue;

                filteredItems.value[key] = filterValue;
            }
        });
    });

    const resetFilterKey = (keyToReset: string = 'all') => {
        const rawUrl = options.routeName ? route(options.routeName) : window.location.href;
        const url = new URL(rawUrl);

        for (const key of url.searchParams.keys()) {
            const rawKey = cleanFilterKey(key);
            if (formKeys.includes(rawKey)) {
                if (keyToReset === 'all' || keyToReset === rawKey) {
                    url.searchParams.delete(key);
                }
            }
        }

        router.visit(url.pathname + '?' + url.searchParams.toString(), {
            preserveScroll: true,
            only: options.preserveKeys ?? [],
            onStart: () => (options.form.processing = true),
            onFinish: () => (options.form.processing = false),
        });
    };

    function cleanFilterKey(key: string) {
        return key.replaceAll('filter', '').replaceAll('[', '').replaceAll(']', '');
    }

    return { filteredQuery, runFilter, filteredItems: filteredItems.value, resetFilterKey };
}

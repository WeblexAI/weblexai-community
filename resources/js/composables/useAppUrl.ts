import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useAppUrl() {
    const page = usePage();

    const MARKETING_URL = computed(() => {
        const base = page.props.marketing_url as string;
        return {
            BASE_URL: base,
            PRICING_URL: `${base}/pricing`,
            PRICING_COMPARISON_URL: `${base}/pricing#comparison`,
            CONTACT_US_URL: `${base}/contact-us`,
        };
    });

    const DASHBOARD_URL = computed(() => {
        const base = page.props.dashboard_url as string;
        return {
            LOGIN_URL: `${base}/login`,
        };
    });

    return {
        MARKETING_URL,
        DASHBOARD_URL,
    };
}

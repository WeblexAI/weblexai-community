import { ProjectI } from '@/types';
import { usePage } from '@inertiajs/vue3';

export default function useProject(): ProjectI {
    const page = usePage();
    return page.props?.project;
}

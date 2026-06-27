import { CollaboratorRoleE } from '@/enums';
import { AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export default function useAuthorization() {
    const page = usePage<AppPageProps>();

    const role = computed(() => page.props.auth.role);
    const user = computed(() => page.props.auth.user);
    const project = computed(() => page.props.project);

    const isOwner = computed(() => role.value === CollaboratorRoleE.OWNER);

    const canManageSettings = computed(() => {
        return isOwner.value || role.value === CollaboratorRoleE.MANAGER;
    });

    const canManageCollaborators = computed(() => {
        return isOwner.value || role.value === CollaboratorRoleE.MANAGER;
    });

    const canManageContent = computed(() => {
        return isOwner.value || role.value === CollaboratorRoleE.MANAGER || role.value === CollaboratorRoleE.TRANSLATOR;
    });

    const isViewer = computed(() => role.value === CollaboratorRoleE.VIEWER);

    return {
        role,
        user,
        project,
        isOwner,
        canManageSettings,
        canManageCollaborators,
        canManageContent,
        isViewer,
    };
}

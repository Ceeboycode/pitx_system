import { usePage } from '@inertiajs/vue3';

type PageProps = {
    auth?: {
        permissions?: string[];
    };
    isArchived?: boolean;
};

/** Permission actions that only read data; every other action changes something. */
const READ_ONLY_ACTIONS = ['view', 'viewAny', 'viewTrash', 'viewOwn', 'download'];

/**
 * Whether the signed-in user holds a permission.
 *
 * Detail pages of archived records (they send `isArchived: true`) are read-only,
 * so every permission that changes data reads as not granted there and the
 * existing `can()`-guarded edit, archive and toggle controls disappear on their own.
 * The write routes also reject archived records on the server.
 */
export function can(permission: string): boolean {
    const page = usePage<PageProps>();

    if (page.props.isArchived === true) {
        const action = permission.split('.').pop() ?? '';

        if (!READ_ONLY_ACTIONS.includes(action)) {
            return false;
        }
    }

    const permissions = page.props.auth?.permissions ?? [];
    return permissions.includes(permission);
}

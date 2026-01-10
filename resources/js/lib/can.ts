import { usePage } from '@inertiajs/vue3';

type PageProps = {
    auth?: {
        permissions?: string[];
    };
};

export function can(permission: string): boolean {
    const page = usePage<PageProps>();
    const permissions = page.props.auth?.permissions ?? [];
    return permissions.includes(permission);
}

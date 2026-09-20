/**
 * Helpers for showing a role's permissions. Permission names follow "module.action"
 * (e.g. "gates.viewAny"); the ones for external roles are prefixed "external_".
 */

export type Permission = {
    id: number;
    name: string;
};

export const isExternalPermission = (permission: Permission): boolean => permission.name.startsWith('external_');

/** The permissions a role of this type may hold: external roles the "external_" ones, internal roles the rest. */
export function permissionsOfType(permissions: Permission[], type: string): Permission[] {
    return permissions.filter((permission) => isExternalPermission(permission) === (type === 'external'));
}

/** "external_company_documents" -> "Company Documents" */
export function moduleLabel(moduleKey: string): string {
    return moduleKey
        .replace(/^external_/, '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

/** "gates.viewAny" -> "View Any" */
export function actionLabel(permissionName: string): string {
    const action = permissionName.split('.').slice(1).join('.') || permissionName;

    return action.replace(/([a-z])([A-Z])/g, '$1 $2').replace(/\b\w/g, (c) => c.toUpperCase());
}

/** Groups permissions by module, modules and their permissions sorted by name. */
export function groupPermissions(permissions: Permission[]): [string, Permission[]][] {
    const groups: Record<string, Permission[]> = {};

    for (const permission of permissions) {
        const moduleKey = permission.name.split('.')[0] || 'other';
        (groups[moduleKey] ??= []).push(permission);
    }

    return Object.entries(groups)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([key, list]) => [key, list.sort((x, y) => x.name.localeCompare(y.name))]);
}

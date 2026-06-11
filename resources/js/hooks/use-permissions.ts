import { usePage } from '@inertiajs/react';

export const SUPER_ADMIN_ROLE = 'super-admin';

/**
 * Permission-aware UI helpers backed by the shared `auth` props (ТЗ §8). The super-admin always
 * passes; other users are checked against their effective permission list.
 */
export function usePermissions() {
    const { auth } = usePage().props;

    const roles = auth?.roles ?? [];
    const permissions = auth?.permissions ?? [];
    const isSuperAdmin = roles.includes(SUPER_ADMIN_ROLE);

    const can = (permission: string): boolean =>
        isSuperAdmin || permissions.includes(permission);

    const canAny = (required: string[]): boolean =>
        isSuperAdmin ||
        required.some((permission) => permissions.includes(permission));

    const hasRole = (role: string): boolean => roles.includes(role);

    return { can, canAny, hasRole, isSuperAdmin, roles, permissions };
}

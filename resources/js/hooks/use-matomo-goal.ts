import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { trackMatomoGoal } from '@/lib/matomo';
import type { MatomoSharedProps } from '@/lib/matomo';

/**
 * Track a Matomo goal once when `condition` becomes true (e.g. success screen rendered).
 */
export function useMatomoGoal(goalKey: string, condition: boolean): void {
    const { matomo } = usePage().props as { matomo?: MatomoSharedProps };
    const tracked = useRef(false);

    useEffect(() => {
        if (!condition || tracked.current || matomo?.enabled !== true) {
            return;
        }

        tracked.current = true;
        trackMatomoGoal(goalKey, matomo.goals);
    }, [condition, goalKey, matomo]);
}

import { useEffect, useRef, useState } from 'react';

export type AutosaveState = 'idle' | 'pending' | 'saving' | 'saved' | 'error';

type Options<T extends Record<string, unknown>> = {
    enabled: boolean;
    url: string;
    data: T;
    delay?: number;
};

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

/**
 * Debounced silent PATCH save for CMS editorial forms (draft working copy).
 */
export function useAutosave<T extends Record<string, unknown>>({
    enabled,
    url,
    data,
    delay = 3000,
}: Options<T>): { state: AutosaveState; savedAt: Date | null } {
    const [state, setState] = useState<AutosaveState>('idle');
    const [savedAt, setSavedAt] = useState<Date | null>(null);
    const isFirstRun = useRef(true);
    const serialized = JSON.stringify(data);

    useEffect(() => {
        if (!enabled || !url) {
            return;
        }

        if (isFirstRun.current) {
            isFirstRun.current = false;

            return;
        }

        setState('pending');

        const timer = window.setTimeout(() => {
            setState('saving');

            fetch(url, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            })
                .then(async (response) => {
                    if (!response.ok) {
                        throw new Error('autosave failed');
                    }

                    await response.json();
                    setState('saved');
                    setSavedAt(new Date());
                })
                .catch(() => setState('error'));
        }, delay);

        return () => window.clearTimeout(timer);
    }, [data, delay, enabled, serialized, url]);

    return { state, savedAt };
}

import { usePage, usePoll } from '@inertiajs/react';
import { TriangleAlert, X } from 'lucide-react';
import { useState } from 'react';

const STORAGE_KEY = 'kchs-dismissed-alerts';

function loadDismissed(): number[] {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]') as number[];
    } catch {
        return [];
    }
}

/**
 * Site-wide emergency alert banner (ТЗ §6.4.1). Reads active alerts from shared props and refreshes
 * them via Inertia polling (D-11). Critical alerts are pinned; dismissible ones are remembered in
 * localStorage so they stay closed across navigation.
 */
export function AlertBanner() {
    const { activeAlerts } = usePage().props;
    usePoll(60000, { only: ['activeAlerts'] });

    const [dismissed, setDismissed] = useState<number[]>(loadDismissed);

    const visible = (activeAlerts ?? []).filter(
        (alert) => !(alert.dismissible && dismissed.includes(alert.id)),
    );

    if (visible.length === 0) {
        return null;
    }

    const dismiss = (id: number) => {
        const next = [...dismissed, id];
        setDismissed(next);

        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
        } catch {
            // Ignore storage failures (private mode etc.).
        }
    };

    return (
        <div role="alert">
            {visible.map((alert) => (
                <div key={alert.id} style={{ backgroundColor: alert.color }} className="text-white">
                    <div className="mx-auto flex max-w-6xl items-start gap-3 px-4 py-3">
                        <TriangleAlert className="mt-0.5 size-5 shrink-0" />
                        <div className="flex-1">
                            <p className="text-xs font-semibold uppercase opacity-90">{alert.level_label}</p>
                            <p className="font-semibold">{alert.title}</p>
                            {alert.body && <p className="text-sm opacity-90">{alert.body}</p>}
                        </div>
                        {alert.dismissible && (
                            <button
                                type="button"
                                onClick={() => dismiss(alert.id)}
                                aria-label="Закрыть"
                                className="shrink-0 rounded p-1 hover:bg-white/20"
                            >
                                <X className="size-4" />
                            </button>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}

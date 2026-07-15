import { Link, usePage, usePoll } from '@inertiajs/react';
import { ArrowRight, TriangleAlert, X } from 'lucide-react';
import { useState } from 'react';
import { useTranslations } from '@/hooks/use-translations';

const STORAGE_KEY = 'kchs-dismissed-alerts';

/**
 * Severity-driven tints keep the banner calm and readable regardless of the per-alert color set in
 * the CMS — a contained strip instead of a full-bleed saturated band (ТЗ §6.4.1).
 */
const TINT: Record<string, string> = {
    critical:
        'border-red-300 bg-red-50 dark:border-red-900/60 dark:bg-red-950/40',
    danger: 'border-orange-300 bg-orange-50 dark:border-orange-900/60 dark:bg-orange-950/40',
    elevated:
        'border-amber-300 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/40',
    info: 'border-sky-300 bg-sky-50 dark:border-sky-900/60 dark:bg-sky-950/40',
};

const ACCENT: Record<string, string> = {
    critical: 'text-red-600 dark:text-red-400',
    danger: 'text-orange-600 dark:text-orange-400',
    elevated: 'text-amber-600 dark:text-amber-400',
    info: 'text-sky-600 dark:text-sky-400',
};

const tintFor = (level: string): string => TINT[level] ?? TINT.elevated;
const accentFor = (level: string): string => ACCENT[level] ?? ACCENT.elevated;

function loadDismissed(): number[] {
    try {
        return JSON.parse(
            localStorage.getItem(STORAGE_KEY) ?? '[]',
        ) as number[];
    } catch {
        return [];
    }
}

/**
 * Site-wide emergency alert banner (ТЗ §6.4.1). Reads active alerts from shared props and refreshes
 * them via Inertia polling (D-11). Critical alerts are pinned; dismissible ones are remembered in
 * localStorage so they stay closed across navigation. Rendered as a contained, severity-tinted strip.
 */
export function AlertBanner() {
    const { t } = useTranslations();
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
        <div
            role="alert"
            className="border-b border-border bg-background print:hidden"
        >
            <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-2.5">
                {visible.map((alert) => (
                    <div
                        key={alert.id}
                        className={`flex items-start gap-3 rounded-lg border px-3.5 py-2.5 ${tintFor(alert.level)}`}
                    >
                        <TriangleAlert
                            className={`mt-0.5 size-4.5 shrink-0 ${accentFor(alert.level)}`}
                            aria-hidden="true"
                        />
                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                <span
                                    className={`text-[11px] font-bold tracking-wide uppercase ${accentFor(alert.level)}`}
                                >
                                    {alert.level_label}
                                </span>
                                <span className="font-semibold text-foreground">
                                    {alert.title}
                                </span>
                            </div>
                            {alert.body && (
                                <p className="mt-0.5 text-sm leading-relaxed text-muted-foreground">
                                    {alert.body}
                                </p>
                            )}
                            <Link
                                href={alert.url}
                                className={`mt-1 inline-flex items-center gap-1 text-xs font-medium ${accentFor(alert.level)} hover:underline`}
                            >
                                {t('alerts.more')}
                                <ArrowRight className="size-3.5" aria-hidden="true" />
                            </Link>
                        </div>
                        {alert.dismissible && (
                            <button
                                type="button"
                                onClick={() => dismiss(alert.id)}
                                aria-label={t('common.close')}
                                className="shrink-0 rounded-md p-1 text-muted-foreground transition-colors hover:bg-foreground/5 hover:text-foreground"
                            >
                                <X className="size-4" aria-hidden="true" />
                            </button>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

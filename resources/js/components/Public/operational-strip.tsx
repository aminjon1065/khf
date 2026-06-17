import { usePage } from '@inertiajs/react';
import { Phone } from 'lucide-react';
import type { OperationalSummary } from '@/components/Public/gov-hero';
import { useTranslations } from '@/hooks/use-translations';

type StatusKey = 'normal' | 'elevated' | 'danger';

const STATUS_DOT: Record<StatusKey, string> = {
    normal: 'bg-hazard-normal',
    elevated: 'bg-hazard-elevated',
    danger: 'bg-hazard-danger',
};

/**
 * Compact operational-situation bar shown beneath the homepage news carousel (ТЗ §5, §6.1). Keeps the
 * live hazard status, incident counts and the 112 hotline present and high on the page without the
 * weight of a full hero card — the news slider is the lead, this stays a calm one-line strip.
 */
export function OperationalStrip({
    operational,
}: {
    operational?: OperationalSummary;
}) {
    const { t } = useTranslations();
    const { activeAlerts } = usePage().props as {
        activeAlerts?: Array<{ level: string }>;
    };

    const levels = new Set((activeAlerts ?? []).map((alert) => alert.level));
    const status: StatusKey = levels.has('danger')
        ? 'danger'
        : levels.has('elevated')
          ? 'elevated'
          : 'normal';

    const activeCount = operational?.active ?? 0;

    return (
        <section
            aria-label={t('home.operational.title')}
            className="mt-6 flex flex-wrap items-center justify-between gap-x-6 gap-y-3 rounded-xl border border-border bg-card px-5 py-3.5 shadow-sm"
        >
            <div className="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                <span className="inline-flex items-center gap-2 font-semibold text-foreground">
                    <span className="relative flex size-2.5">
                        <span
                            className={`absolute inline-flex h-full w-full rounded-full ${STATUS_DOT[status]} opacity-60 motion-safe:animate-ping`}
                        />
                        <span
                            className={`relative inline-flex size-2.5 rounded-full ${STATUS_DOT[status]}`}
                        />
                    </span>
                    {t('home.status.label')}
                    <span className="font-normal text-muted-foreground">
                        — {t(`home.status.${status}`)}
                    </span>
                </span>

                {activeCount > 0 ? (
                    <span className="flex flex-wrap items-center gap-x-4 gap-y-1 text-muted-foreground">
                        <span>
                            <span className="font-bold text-foreground">
                                {operational?.active}
                            </span>{' '}
                            {t('home.operational.active')}
                        </span>
                        <span>
                            <span className="font-bold text-foreground">
                                {operational?.controlled}
                            </span>{' '}
                            {t('home.operational.controlled')}
                        </span>
                    </span>
                ) : (
                    <span className="inline-flex items-center gap-2 text-muted-foreground">
                        <span className="size-2 rounded-full bg-hazard-normal" />
                        {t('home.operational.all_clear')}
                    </span>
                )}
            </div>

            <a
                href="tel:112"
                className="inline-flex items-center gap-2 rounded-lg bg-signal px-4 py-2 text-sm font-bold text-signal-foreground transition-colors hover:bg-signal/90 focus-visible:ring-2 focus-visible:ring-signal focus-visible:ring-offset-2 focus-visible:outline-none"
            >
                <Phone className="size-4" aria-hidden="true" />
                {t('home.hero.emergency_call')}
            </a>
        </section>
    );
}

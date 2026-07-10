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
            className="mt-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-lg border border-border/80 bg-muted/40 px-4 py-2.5 text-sm"
        >
            <div className="flex flex-wrap items-center gap-x-4 gap-y-1.5">
                <span className="inline-flex items-center gap-2 font-medium text-foreground">
                    <span
                        className={`inline-flex size-2 rounded-full ${STATUS_DOT[status]}`}
                        aria-hidden="true"
                    />
                    {t('home.status.label')}
                    <span className="font-normal text-muted-foreground">
                        — {t(`home.status.${status}`)}
                    </span>
                </span>

                {activeCount > 0 ? (
                    <span className="flex flex-wrap items-center gap-x-3 gap-y-1 text-muted-foreground">
                        <span>
                            <span className="font-semibold text-foreground">
                                {operational?.active}
                            </span>{' '}
                            {t('home.operational.active')}
                        </span>
                        <span>
                            <span className="font-semibold text-foreground">
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
                className="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-signal/25 bg-background px-3 py-1.5 text-xs font-semibold text-signal transition-colors hover:bg-signal/5 focus-visible:ring-2 focus-visible:ring-signal focus-visible:ring-offset-2 focus-visible:outline-none sm:text-sm"
            >
                <Phone className="size-3.5" aria-hidden="true" />
                <span className="tabular-nums">112</span>
            </a>
        </section>
    );
}

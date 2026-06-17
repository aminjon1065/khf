import { Link, usePage } from '@inertiajs/react';
import { Activity, BookOpen, Map, Phone } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';
import { index as guidesIndex } from '@/routes/guides';
import { index as mapIndex } from '@/routes/map';

export type OperationalSummary = {
    active: number;
    controlled: number;
    resolved: number;
};

type StatusKey = 'normal' | 'elevated' | 'danger';

const STATUS_DOT: Record<StatusKey, string> = {
    normal: 'bg-hazard-normal',
    elevated: 'bg-hazard-elevated',
    danger: 'bg-hazard-danger',
};

/**
 * Static govtech hero for the non-emergency homepage state (replaces the auto-rotating carousel —
 * a known a11y anti-pattern). Leads with a live operational-status indicator derived from the active
 * alerts (critical is handled separately by `EmergencyHero`), the КЧС mission line, the two primary
 * citizen actions, and the 112 hotline. Rendered on a light card surface for a calm, institutional
 * tone — red is reserved as an accent for the 112 hotline only (ТЗ §6.1, §11.2).
 */
export function GovHero({ operational }: { operational?: OperationalSummary }) {
    const { t } = useTranslations();
    const { locale, activeAlerts } = usePage().props as {
        locale: string;
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
            aria-labelledby="gov-hero-title"
            className="overflow-hidden rounded-2xl border border-border bg-card shadow-sm"
        >
            <div className="grid gap-8 p-8 sm:p-10 lg:grid-cols-[1.6fr_1fr] lg:items-center">
                <div>
                    <span className="inline-flex items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground">
                        <span className="relative flex size-2.5">
                            <span
                                className={`absolute inline-flex h-full w-full rounded-full ${STATUS_DOT[status]} opacity-60 motion-safe:animate-ping`}
                            />
                            <span
                                className={`relative inline-flex size-2.5 rounded-full ${STATUS_DOT[status]}`}
                            />
                        </span>
                        {t('home.status.label')} — {t(`home.status.${status}`)}
                    </span>

                    <h1
                        id="gov-hero-title"
                        className="mt-4 text-3xl leading-tight font-bold tracking-tight text-balance text-foreground sm:text-4xl"
                    >
                        {t('home.hero.title')}
                    </h1>
                    <p className="mt-4 max-w-xl text-base leading-relaxed text-muted-foreground">
                        {t('home.hero.subtitle')}
                    </p>

                    <div className="mt-7 flex flex-wrap gap-3">
                        <Link
                            href={mapIndex({ locale }).url}
                            className="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <Map className="size-4.5" aria-hidden="true" />
                            {t('home.hero.map_cta')}
                        </Link>
                        <Link
                            href={guidesIndex({ locale }).url}
                            className="inline-flex items-center gap-2 rounded-lg border border-border px-5 py-3 text-sm font-semibold text-foreground transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <BookOpen className="size-4.5" aria-hidden="true" />
                            {t('home.hero.guides_cta')}
                        </Link>
                    </div>

                    <div className="mt-8 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-border pt-5 text-sm">
                        <span className="flex items-center gap-2 font-semibold text-muted-foreground">
                            <Activity className="size-4" aria-hidden="true" />
                            {t('home.operational.title')}
                        </span>
                        {activeCount > 0 ? (
                            <>
                                <span className="text-muted-foreground">
                                    <span className="font-bold text-foreground">
                                        {operational?.active}
                                    </span>{' '}
                                    {t('home.operational.active')}
                                </span>
                                <span className="text-muted-foreground">
                                    <span className="font-bold text-foreground">
                                        {operational?.controlled}
                                    </span>{' '}
                                    {t('home.operational.controlled')}
                                </span>
                            </>
                        ) : (
                            <span className="inline-flex items-center gap-2 text-muted-foreground">
                                <span className="size-2 rounded-full bg-hazard-normal" />
                                {t('home.operational.all_clear')}
                            </span>
                        )}
                    </div>
                </div>

                <a
                    href="tel:112"
                    className="group flex flex-col items-center justify-center gap-1 rounded-xl border border-border bg-background p-6 text-center transition-colors hover:border-signal/40 hover:bg-signal/5 focus-visible:ring-2 focus-visible:ring-signal focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <span className="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                        {t('home.hero.call_label')}
                    </span>
                    <span className="flex items-center justify-center gap-3 text-5xl font-extrabold tracking-tight text-signal tabular-nums">
                        <Phone className="size-8" aria-hidden="true" />
                        112
                    </span>
                    <span className="text-xs text-muted-foreground">
                        {t('home.hero.call_note')}
                    </span>
                </a>
            </div>
        </section>
    );
}

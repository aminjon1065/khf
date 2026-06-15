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
 * citizen actions, and the 112 hotline. On the brand-navy chrome (ТЗ §6.1, §11.2).
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
            className="overflow-hidden rounded-2xl bg-brand text-brand-foreground shadow-sm"
        >
            <div className="grid gap-8 p-8 sm:p-10 lg:grid-cols-[1.6fr_1fr] lg:items-center">
                <div>
                    <span className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-semibold tracking-wide">
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
                        className="mt-5 text-3xl leading-tight font-bold tracking-tight sm:text-4xl"
                    >
                        {t('home.hero.title')}
                    </h1>
                    <p className="mt-4 max-w-xl text-base leading-relaxed text-brand-foreground/80">
                        {t('home.hero.subtitle')}
                    </p>

                    <div className="mt-7 flex flex-wrap gap-3">
                        <Link
                            href={mapIndex({ locale }).url}
                            className="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-bold text-brand shadow-sm transition-transform hover:scale-[1.02] focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand focus-visible:outline-none"
                        >
                            <Map className="size-4.5" aria-hidden="true" />
                            {t('home.hero.map_cta')}
                        </Link>
                        <Link
                            href={guidesIndex({ locale }).url}
                            className="inline-flex items-center gap-2 rounded-lg border border-white/30 px-5 py-3 text-sm font-semibold text-brand-foreground transition-colors hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                        >
                            <BookOpen className="size-4.5" aria-hidden="true" />
                            {t('home.hero.guides_cta')}
                        </Link>
                    </div>

                    <div className="mt-8 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-white/15 pt-5 text-sm">
                        <span className="flex items-center gap-2 font-semibold text-brand-foreground/70">
                            <Activity className="size-4" aria-hidden="true" />
                            {t('home.operational.title')}
                        </span>
                        {activeCount > 0 ? (
                            <>
                                <span>
                                    <span className="font-bold">
                                        {operational?.active}
                                    </span>{' '}
                                    <span className="text-brand-foreground/70">
                                        {t('home.operational.active')}
                                    </span>
                                </span>
                                <span>
                                    <span className="font-bold">
                                        {operational?.controlled}
                                    </span>{' '}
                                    <span className="text-brand-foreground/70">
                                        {t('home.operational.controlled')}
                                    </span>
                                </span>
                            </>
                        ) : (
                            <span className="inline-flex items-center gap-2 text-brand-foreground/80">
                                <span className="size-2 rounded-full bg-hazard-normal" />
                                {t('home.operational.all_clear')}
                            </span>
                        )}
                    </div>
                </div>

                <a
                    href="tel:112"
                    className="group flex flex-col items-center justify-center gap-1 rounded-xl border border-white/15 bg-white/[0.07] p-6 text-center transition-colors hover:bg-white/[0.12] focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                >
                    <span className="text-xs font-medium tracking-wider text-brand-foreground/70 uppercase">
                        {t('home.hero.call_label')}
                    </span>
                    <span className="flex items-center justify-center gap-3 text-5xl font-extrabold tracking-tight">
                        <Phone
                            className="size-8 motion-safe:animate-pulse"
                            aria-hidden="true"
                        />
                        112
                    </span>
                    <span className="text-xs text-brand-foreground/70">
                        {t('home.hero.call_note')}
                    </span>
                </a>
            </div>
        </section>
    );
}

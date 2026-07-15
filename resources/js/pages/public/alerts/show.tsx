import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { HazardBadge } from '@/components/hazard-badge';
import type { HazardLevel } from '@/components/hazard-badge';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/utils';
import { welcome } from '@/routes';

type AlertDetail = {
    id: number;
    level: HazardLevel;
    level_label: string;
    color: string;
    title: string | null;
    body: string | null;
    region: string | null;
    published_at: string | null;
    expires_at: string | null;
    is_active: boolean;
};

/**
 * Public detail page for an emergency alert (ТЗ §6.4.1). The site banner, alert e-mails and
 * web-push notifications all deep-link here so a citizen can read the full warning, its validity
 * window and the affected region.
 */
export default function AlertShow({ alert }: { alert: AlertDetail }) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    return (
        <>
            <Head title={alert.title ?? alert.level_label} />

            <article className="mx-auto max-w-3xl">
                <Link
                    href={welcome({ locale }).url}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ChevronLeft className="size-4" aria-hidden />
                    {t('alerts.back')}
                </Link>

                <div className="mt-4 flex flex-wrap items-center gap-3">
                    <HazardBadge level={alert.level} label={alert.level_label} />
                    <span
                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                            alert.is_active
                                ? 'bg-hazard-danger/10 text-hazard-danger'
                                : 'bg-muted text-muted-foreground'
                        }`}
                    >
                        {alert.is_active ? t('alerts.active') : t('alerts.expired')}
                    </span>
                </div>

                <h1 className="mt-4 text-3xl font-semibold text-balance">
                    {alert.title}
                </h1>

                <dl className="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted-foreground">
                    {alert.published_at && (
                        <div className="flex gap-1.5">
                            <dt>{t('alerts.published')}:</dt>
                            <dd>
                                <time dateTime={alert.published_at}>
                                    {formatDate(alert.published_at, locale)}
                                </time>
                            </dd>
                        </div>
                    )}
                    {alert.expires_at && (
                        <div className="flex gap-1.5">
                            <dt>{t('alerts.expires')}:</dt>
                            <dd>
                                <time dateTime={alert.expires_at}>
                                    {formatDate(alert.expires_at, locale)}
                                </time>
                            </dd>
                        </div>
                    )}
                    {alert.region && (
                        <div className="flex gap-1.5">
                            <dt>{t('alerts.region')}:</dt>
                            <dd>{alert.region}</dd>
                        </div>
                    )}
                </dl>

                {alert.body && (
                    <div className="rte-content mt-6 leading-relaxed">
                        {alert.body}
                    </div>
                )}
            </article>
        </>
    );
}

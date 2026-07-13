import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Map } from 'lucide-react';
import { useCallback } from 'react';
import type { Paginator } from '@/components/admin/data-table';
import { HazardBadge } from '@/components/hazard-badge';
import type { HazardLevel } from '@/components/hazard-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/utils';
import { index as incidentsIndex } from '@/routes/incidents';
import { index as mapIndex } from '@/routes/map';

type Option = { value: string; label: string };

type IncidentItem = {
    title: string | null;
    description: string | null;
    type_label: string;
    hazard_level: HazardLevel;
    hazard_label: string;
    status: string;
    status_label: string;
    region: string | null;
    occurred_at: string | null;
};

type Summary = {
    active: number;
    controlled: number;
    resolved: number;
};

type PageProps = {
    incidents: Paginator<IncidentItem> & {
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    summary: Summary;
    filters: {
        type?: string;
        level?: string;
        region?: string;
        period?: string;
    };
    types: Option[];
    levels: Option[];
    regions: Option[];
};

export default function IncidentsArchive({
    incidents,
    summary,
    filters,
    types,
    levels,
    regions,
}: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    const stats = [
        { key: 'active', value: summary.active, tone: 'text-hazard-danger' },
        {
            key: 'controlled',
            value: summary.controlled,
            tone: 'text-hazard-elevated',
        },
        {
            key: 'resolved',
            value: summary.resolved,
            tone: 'text-hazard-normal',
        },
    ] as const;

    const applyFilter = useCallback(
        (key: string, value: string) => {
            router.get(
                incidentsIndex.url(locale as string),
                { ...filters, [key]: value === 'all' ? null : value, page: 1 },
                { preserveState: true, replace: true },
            );
        },
        [filters, locale],
    );

    return (
        <>
            <Head title={t('common.operational_situation')} />

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-semibold">
                        {t('common.operational_situation')}
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        {t('incidents.subtitle')}
                    </p>
                </div>
                <Button variant="outline" asChild>
                    <Link href={mapIndex({ locale }).url}>
                        <Map className="mr-2 size-4" />
                        {t('incidents.view_map')}
                    </Link>
                </Button>
            </div>

            <div className="mb-8 grid gap-4 sm:grid-cols-3">
                {stats.map((stat) => (
                    <div key={stat.key} className="rounded-lg border p-4">
                        <p className={`text-3xl font-semibold ${stat.tone}`}>
                            {stat.value}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {t(`incidents.summary.${stat.key}`)}
                        </p>
                    </div>
                ))}
            </div>

            <div className="mb-6 flex flex-wrap items-center justify-start gap-3 rounded-lg border bg-muted/50 p-4">
                <div className="flex min-w-[160px] flex-col gap-1">
                    <label
                        htmlFor="archive-filter-type"
                        className="text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        {t('map.filter_type')}
                    </label>
                    <select
                        id="archive-filter-type"
                        value={filters.type ?? 'all'}
                        onChange={(e) => applyFilter('type', e.target.value)}
                        className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                    >
                        <option value="all">{t('map.filter_type_all')}</option>
                        {types.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="flex min-w-[160px] flex-col gap-1">
                    <label
                        htmlFor="archive-filter-level"
                        className="text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        {t('map.filter_level')}
                    </label>
                    <select
                        id="archive-filter-level"
                        value={filters.level ?? 'all'}
                        onChange={(e) => applyFilter('level', e.target.value)}
                        className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                    >
                        <option value="all">{t('map.filter_level_all')}</option>
                        {levels.map((level) => (
                            <option key={level.value} value={level.value}>
                                {level.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="flex min-w-[160px] flex-col gap-1">
                    <label
                        htmlFor="archive-filter-region"
                        className="text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Регион
                    </label>
                    <select
                        id="archive-filter-region"
                        value={filters.region ?? 'all'}
                        onChange={(e) => applyFilter('region', e.target.value)}
                        className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                    >
                        <option value="all">Все регионы</option>
                        {regions.map((region) => (
                            <option key={region.value} value={region.value}>
                                {region.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="flex min-w-[160px] flex-col gap-1">
                    <label
                        htmlFor="archive-filter-period"
                        className="text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Период
                    </label>
                    <select
                        id="archive-filter-period"
                        value={filters.period ?? 'all'}
                        onChange={(e) => applyFilter('period', e.target.value)}
                        className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                    >
                        <option value="all">За все время</option>
                        <option value="today">За сегодня</option>
                        <option value="week">За неделю</option>
                        <option value="month">За месяц</option>
                    </select>
                </div>
            </div>

            {incidents.data.length === 0 ? (
                <p className="text-muted-foreground">{t('incidents.empty')}</p>
            ) : (
                <div className="space-y-4">
                    {incidents.data.map((incident, idx) => (
                        <div key={idx} className="rounded-lg border p-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <HazardBadge
                                    level={incident.hazard_level}
                                    label={incident.hazard_label}
                                    size="sm"
                                />
                                <Badge variant="secondary">
                                    {incident.type_label}
                                </Badge>
                                <Badge variant="outline">
                                    {incident.status_label}
                                </Badge>
                                {incident.region && (
                                    <span className="text-sm text-muted-foreground">
                                        {incident.region}
                                    </span>
                                )}
                                {incident.occurred_at && (
                                    <span className="ml-auto text-sm text-muted-foreground">
                                        {formatDate(
                                            incident.occurred_at,
                                            locale,
                                        )}
                                    </span>
                                )}
                            </div>
                            <h2 className="mt-2 text-lg font-semibold">
                                {incident.title}
                            </h2>
                            {incident.description && (
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {incident.description}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {(incidents.prev_page_url || incidents.next_page_url) && (
                <div className="mt-8 flex items-center justify-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!incidents.prev_page_url}
                        onClick={() =>
                            incidents.prev_page_url &&
                            router.get(incidents.prev_page_url)
                        }
                    >
                        <ChevronLeft className="mr-2 size-4" />
                        {t('common.back')}
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!incidents.next_page_url}
                        onClick={() =>
                            incidents.next_page_url &&
                            router.get(incidents.next_page_url)
                        }
                    >
                        {t('common.next')}
                        <ChevronRight className="ml-2 size-4" />
                    </Button>
                </div>
            )}
        </>
    );
}

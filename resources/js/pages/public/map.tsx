import { Head, router } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import { MapView } from '@/components/map-view';
import type { MapMarker } from '@/components/map-view';
import { useTranslations } from '@/hooks/use-translations';

type Option = { value: string; label: string };

type IncidentMarker = {
    id: number;
    lat: number;
    lng: number;
    color: string;
    title: string;
    type: string;
    level: string;
    status: string;
    region: string | null;
    occurred_at: string | null;
};

type PageProps = {
    incidents: IncidentMarker[];
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

export default function PublicMap({
    incidents,
    filters,
    types,
    levels,
    regions,
}: PageProps) {
    const { t } = useTranslations();

    const applyFilter = useCallback(
        (key: string, value: string) => {
            router.get(
                route('map.index'),
                { ...filters, [key]: value === 'all' ? null : value },
                { preserveState: true, replace: true }
            );
        },
        [filters]
    );

    const markers = useMemo<MapMarker[]>(
        () =>
            incidents.map((incident) => ({
                id: incident.id,
                lat: incident.lat,
                lng: incident.lng,
                color: incident.color,
                title: incident.title,
                lines: [
                    incident.type,
                    incident.level,
                    incident.status,
                    incident.region ?? '',
                    incident.occurred_at ?? '',
                ].filter(Boolean),
            })),
        [incidents],
    );

    return (
        <>
            <Head title={t('common.emergency_map')} />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-4 border-b pb-4">
                <div>
                    <h1 className="text-3xl font-semibold">
                        {t('map.heading')}
                    </h1>
                    <p className="text-muted-foreground">{t('map.subtitle')}</p>
                </div>

                <div className="flex flex-wrap gap-3">
                    <div className="flex min-w-[160px] flex-col gap-1">
                        <label
                            htmlFor="map-filter-type"
                            className="text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            {t('map.filter_type')}
                        </label>
                        <select
                            id="map-filter-type"
                            value={filters.type ?? 'all'}
                            onChange={(e) => applyFilter('type', e.target.value)}
                            className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                        >
                            <option value="all">
                                {t('map.filter_type_all')}
                            </option>
                            {types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex min-w-[160px] flex-col gap-1">
                        <label
                            htmlFor="map-filter-level"
                            className="text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            {t('map.filter_level')}
                        </label>
                        <select
                            id="map-filter-level"
                            value={filters.level ?? 'all'}
                            onChange={(e) => applyFilter('level', e.target.value)}
                            className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                        >
                            <option value="all">
                                {t('map.filter_level_all')}
                            </option>
                            {levels.map((level) => (
                                <option key={level.value} value={level.value}>
                                    {level.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex min-w-[160px] flex-col gap-1">
                        <label
                            htmlFor="map-filter-region"
                            className="text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            Регион
                        </label>
                        <select
                            id="map-filter-region"
                            value={filters.region ?? 'all'}
                            onChange={(e) => applyFilter('region', e.target.value)}
                            className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                        >
                            <option value="all">
                                Все регионы
                            </option>
                            {regions.map((region) => (
                                <option key={region.value} value={region.value}>
                                    {region.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    
                    <div className="flex min-w-[160px] flex-col gap-1">
                        <label
                            htmlFor="map-filter-period"
                            className="text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            Период
                        </label>
                        <select
                            id="map-filter-period"
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
            </div>

            <div className="h-[70vh] overflow-hidden rounded-lg border relative group">
                {/* Optional overlay logic could go here */}
                <MapView markers={markers} />
            </div>
        </>
    );
}

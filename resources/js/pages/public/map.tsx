import { Head, router, usePage } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import { MapView } from '@/components/map-view';
import type {
    MapLayerVisibility,
    MapMarker,
    MapUnitMarker,
} from '@/components/map-view';
import { Checkbox } from '@/components/ui/checkbox';
import { useTranslations } from '@/hooks/use-translations';
import { index as mapIndex } from '@/routes/map';
import type { SharedData } from '@/types';

type Option = { value: string; label: string };

type IncidentTypeOption = {
    value: string;
    label: string;
    color: string;
    icon: string;
};

type IncidentMarker = {
    id: number;
    lat: number;
    lng: number;
    color: string;
    title: string;
    type_key: string;
    type: string;
    level: string;
    status: string;
    region: string | null;
    occurred_at: string | null;
};

type RiskZonesGeoJson = {
    type: 'FeatureCollection';
    features: Array<{
        type: 'Feature';
        properties: { id: string; name: string; color: string };
        geometry: { type: 'Polygon'; coordinates: number[][][] };
    }>;
};

type PageProps = {
    incidents: IncidentMarker[];
    units: MapUnitMarker[];
    riskZones: RiskZonesGeoJson;
    filters: {
        type?: string;
        level?: string;
        region?: string;
        period?: string;
    };
    incidentTypes: IncidentTypeOption[];
    types: Option[];
    levels: Option[];
    regions: Option[];
};

function buildInitialTypeVisibility(
    incidentTypes: IncidentTypeOption[],
): Record<string, boolean> {
    return Object.fromEntries(incidentTypes.map((type) => [type.value, true]));
}

export default function PublicMap({
    incidents,
    units,
    riskZones,
    filters,
    incidentTypes,
    types,
    levels,
    regions,
}: PageProps) {
    const { locale } = usePage<SharedData>().props;
    const { t } = useTranslations();
    const [typeVisibility, setTypeVisibility] = useState(() =>
        buildInitialTypeVisibility(incidentTypes),
    );
    const [showUnits, setShowUnits] = useState(true);
    const [showRiskZones, setShowRiskZones] = useState(false);

    const applyFilter = useCallback(
        (key: string, value: string) => {
            router.get(
                mapIndex.url(locale),
                { ...filters, [key]: value === 'all' ? null : value },
                { preserveState: true, replace: true },
            );
        },
        [filters, locale],
    );

    const visibleIncidents = useMemo(
        () =>
            incidents.filter(
                (incident) => typeVisibility[incident.type_key] !== false,
            ),
        [incidents, typeVisibility],
    );

    const markers = useMemo<MapMarker[]>(
        () =>
            visibleIncidents.map((incident) => ({
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
        [visibleIncidents],
    );

    const layerVisibility = useMemo<MapLayerVisibility>(
        () => ({
            incidents: Object.values(typeVisibility).some(Boolean),
            units: showUnits,
            riskZones: showRiskZones,
        }),
        [typeVisibility, showUnits, showRiskZones],
    );

    const toggleIncidentType = useCallback(
        (value: string, checked: boolean) => {
            setTypeVisibility((current) => ({ ...current, [value]: checked }));
        },
        [],
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
                            onChange={(e) =>
                                applyFilter('type', e.target.value)
                            }
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
                            onChange={(e) =>
                                applyFilter('level', e.target.value)
                            }
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
                            {t('map.filter_region')}
                        </label>
                        <select
                            id="map-filter-region"
                            value={filters.region ?? 'all'}
                            onChange={(e) =>
                                applyFilter('region', e.target.value)
                            }
                            className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                        >
                            <option value="all">
                                {t('map.filter_region_all')}
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
                            {t('map.filter_period')}
                        </label>
                        <select
                            id="map-filter-period"
                            value={filters.period ?? 'all'}
                            onChange={(e) =>
                                applyFilter('period', e.target.value)
                            }
                            className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                        >
                            <option value="all">
                                {t('map.filter_period_all')}
                            </option>
                            <option value="today">
                                {t('map.filter_period_today')}
                            </option>
                            <option value="week">
                                {t('map.filter_period_week')}
                            </option>
                            <option value="month">
                                {t('map.filter_period_month')}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div className="relative h-[70vh] overflow-hidden rounded-lg border">
                <div className="absolute top-3 left-3 z-10 max-h-[calc(100%-1.5rem)] w-56 overflow-y-auto rounded-lg border border-border bg-card/95 p-3 shadow-md backdrop-blur-sm">
                    <p className="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {t('map.layers_title')}
                    </p>

                    <div className="space-y-3">
                        <label className="flex cursor-pointer items-center gap-2 text-sm">
                            <Checkbox
                                checked={showUnits}
                                onCheckedChange={(checked) =>
                                    setShowUnits(checked === true)
                                }
                            />
                            <span>{t('map.layer_units')}</span>
                        </label>

                        <label className="flex cursor-pointer items-center gap-2 text-sm">
                            <Checkbox
                                checked={showRiskZones}
                                onCheckedChange={(checked) =>
                                    setShowRiskZones(checked === true)
                                }
                            />
                            <span>{t('map.layer_risk_zones')}</span>
                        </label>

                        <div>
                            <p className="mb-1.5 text-xs font-medium text-muted-foreground">
                                {t('map.layer_incidents')}
                            </p>
                            <div className="space-y-1.5">
                                {incidentTypes.map((type) => (
                                    <label
                                        key={type.value}
                                        className="flex cursor-pointer items-center gap-2 text-sm"
                                    >
                                        <Checkbox
                                            checked={
                                                typeVisibility[type.value] !==
                                                false
                                            }
                                            onCheckedChange={(checked) =>
                                                toggleIncidentType(
                                                    type.value,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <span
                                            className="size-2.5 shrink-0 rounded-full"
                                            style={{
                                                backgroundColor: type.color,
                                            }}
                                        />
                                        <span className="truncate">
                                            {type.label}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                <MapView
                    markers={markers}
                    unitMarkers={units}
                    riskZones={riskZones}
                    layerVisibility={layerVisibility}
                />
            </div>
        </>
    );
}

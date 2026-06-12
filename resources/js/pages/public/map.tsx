import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { MapView } from '@/components/map-view';
import type { MapMarker } from '@/components/map-view';
import { useTranslations } from '@/hooks/use-translations';

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
};

export default function PublicMap({ incidents }: PageProps) {
    const { t } = useTranslations();
    const [selectedType, setSelectedType] = useState<string>('all');
    const [selectedLevel, setSelectedLevel] = useState<string>('all');

    const uniqueTypes = useMemo(() => {
        const types = new Set(incidents.map((i) => i.type));

        return ['all', ...Array.from(types)];
    }, [incidents]);

    const uniqueLevels = useMemo(() => {
        const levels = new Set(incidents.map((i) => i.level));

        return ['all', ...Array.from(levels)];
    }, [incidents]);

    const filteredIncidents = useMemo(() => {
        return incidents.filter((incident) => {
            const matchType =
                selectedType === 'all' || incident.type === selectedType;
            const matchLevel =
                selectedLevel === 'all' || incident.level === selectedLevel;

            return matchType && matchLevel;
        });
    }, [incidents, selectedType, selectedLevel]);

    const markers = useMemo<MapMarker[]>(
        () =>
            filteredIncidents.map((incident) => ({
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
        [filteredIncidents],
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
                            value={selectedType}
                            onChange={(e) => setSelectedType(e.target.value)}
                            className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                        >
                            <option value="all">
                                {t('map.filter_type_all')}
                            </option>
                            {uniqueTypes
                                .filter((value) => value !== 'all')
                                .map((type) => (
                                    <option key={type} value={type}>
                                        {type}
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
                            value={selectedLevel}
                            onChange={(e) => setSelectedLevel(e.target.value)}
                            className="w-full cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                        >
                            <option value="all">
                                {t('map.filter_level_all')}
                            </option>
                            {uniqueLevels
                                .filter((value) => value !== 'all')
                                .map((level) => (
                                    <option key={level} value={level}>
                                        {level}
                                    </option>
                                ))}
                        </select>
                    </div>
                </div>
            </div>

            <div className="h-[70vh] overflow-hidden rounded-lg border">
                <MapView markers={markers} />
            </div>
        </>
    );
}

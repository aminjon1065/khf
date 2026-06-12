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
            const matchType = selectedType === 'all' || incident.type === selectedType;
            const matchLevel = selectedLevel === 'all' || incident.level === selectedLevel;
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

            <div className="mb-6 flex flex-wrap gap-4 items-center justify-between border-b pb-4">
                <div>
                    <h1 className="text-3xl font-semibold">{t('map.heading')}</h1>
                    <p className="text-muted-foreground">{t('map.subtitle')}</p>
                </div>
                
                <div className="flex flex-wrap gap-3">
                    <div className="flex flex-col gap-1 min-w-[160px]">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Тип события</span>
                        <select
                            value={selectedType}
                            onChange={(e) => setSelectedType(e.target.value)}
                            className="w-full rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring cursor-pointer"
                        >
                            <option value="all">Все типы</option>
                            {uniqueTypes.filter((t) => t !== 'all').map((type) => (
                                <option key={type} value={type}>{type}</option>
                            ))}
                        </select>
                    </div>

                    <div className="flex flex-col gap-1 min-w-[160px]">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Уровень опасности</span>
                        <select
                            value={selectedLevel}
                            onChange={(e) => setSelectedLevel(e.target.value)}
                            className="w-full rounded-md border border-border bg-card px-3 py-1.5 text-xs shadow-sm transition-colors focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring cursor-pointer"
                        >
                            <option value="all">Все уровни</option>
                            {uniqueLevels.filter((l) => l !== 'all').map((level) => (
                                <option key={level} value={level}>{level}</option>
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

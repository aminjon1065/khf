import { Head } from '@inertiajs/react';
import { useMemo } from 'react';
import { MapView  } from '@/components/map-view';
import type {MapMarker} from '@/components/map-view';

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
            <Head title="Карта ЧС" />

            <div className="mb-4">
                <h1 className="text-3xl font-semibold">Интерактивная карта ЧС</h1>
                <p className="text-muted-foreground">Активные события на территории Республики Таджикистан</p>
            </div>

            <div className="h-[70vh] overflow-hidden rounded-lg border">
                <MapView markers={markers} />
            </div>
        </>
    );
}

import { Link } from '@inertiajs/react';
import { ArrowRight, Map } from 'lucide-react';
import { MapView } from '@/components/map-view';
import type { MapMarker } from '@/components/map-view';
import { useTranslations } from '@/hooks/use-translations';
import { index as mapIndex } from '@/routes/map';

type MapWidgetProps = {
    locale: string;
    incidents: Array<{
        id: number;
        lat: number;
        lng: number;
        color: string;
        title: string;
        type: string;
        level: string;
        status: string;
        region?: string | null;
        occurred_at?: string | null;
    }>;
};

export function MapWidget({ locale, incidents }: MapWidgetProps) {
    const { t } = useTranslations();

    const markers: MapMarker[] = incidents.map((incident) => ({
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
    }));

    return (
        <div className="flex flex-col overflow-hidden rounded-2xl border bg-card shadow-sm transition-all duration-300 hover:shadow-md">
            <div className="flex items-center justify-between border-b px-5 py-4">
                <div className="flex items-center gap-2">
                    <span className="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Map className="size-4.5" />
                    </span>
                    <h3 className="font-semibold text-foreground">
                        {t('common.emergency_map')}
                    </h3>
                </div>
                <Link
                    href={mapIndex({ locale }).url}
                    className="group inline-flex items-center gap-1 text-sm font-medium text-primary transition-colors hover:text-primary/80"
                >
                    {t('common.open_full_map')}
                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                </Link>
            </div>
            <div className="h-64 w-full relative">
                <MapView markers={markers} />
            </div>
        </div>
    );
}

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
        <div className="flex flex-col overflow-hidden rounded-xl border border-border/80 bg-card shadow-sm">
            <div className="flex items-center justify-between gap-3 border-b border-border/60 px-4 py-3">
                <div className="flex min-w-0 items-center gap-2">
                    <span className="flex size-7 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <Map className="size-4" />
                    </span>
                    <div className="min-w-0">
                        <h3 className="truncate text-sm font-semibold text-foreground">
                            {t('common.emergency_map')}
                        </h3>
                        <p className="truncate text-xs text-muted-foreground">
                            {t('common.operational_situation')}
                        </p>
                    </div>
                </div>
                <Link
                    href={mapIndex({ locale }).url}
                    className="group inline-flex shrink-0 items-center gap-1 text-xs font-medium text-primary transition-colors hover:text-primary/80 sm:text-sm"
                >
                    {t('common.open_full_map')}
                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" />
                </Link>
            </div>
            <div className="relative h-44 w-full sm:h-52 lg:h-56">
                <MapView markers={markers} />
            </div>
        </div>
    );
}

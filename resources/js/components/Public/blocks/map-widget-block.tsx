import { MapView } from '@/components/map-view';
import type { MapMarker } from '@/components/map-view';
import type { BlockComponentProps } from '@/components/Public/blocks/types';

export function MapWidgetBlock({ block }: BlockComponentProps) {
    const lat = parseFloat(block.data.lat);
    const lng = parseFloat(block.data.lng);
    const zoom = parseInt(block.data.zoom, 10) || 10;

    if (Number.isNaN(lat) || Number.isNaN(lng)) {
        return null;
    }

    const markers: MapMarker[] = [
        {
            id: block.id,
            lat,
            lng,
            color: '#1f4e8c',
            title: block.data.title || '',
        },
    ];

    return (
        <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div className="aspect-[2/1] w-full">
                <MapView markers={markers} center={[lng, lat]} zoom={zoom} />
            </div>
        </div>
    );
}

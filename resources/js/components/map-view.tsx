import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { useEffect, useRef } from 'react';
import { cn } from '@/lib/utils';

export type MapMarker = {
    id: number | string;
    lat: number;
    lng: number;
    color: string;
    title: string;
    lines?: string[];
};

type MapViewProps = {
    markers?: MapMarker[];
    center?: [number, number];
    zoom?: number;
    className?: string;
    onPick?: (coords: { lat: number; lng: number }) => void;
};

// OSM raster tiles. For production, point this at the Committee's own OSM-compatible tile server
// for independence from external providers (ТЗ §10.8).
const mapStyle: maplibregl.StyleSpecification = {
    version: 8,
    sources: {
        osm: {
            type: 'raster',
            tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
            tileSize: 256,
            attribution: '© OpenStreetMap',
        },
    },
    layers: [{ id: 'osm', type: 'raster', source: 'osm' }],
};

/**
 * Reusable MapLibre map (ТЗ §6.3). Plots colour-coded markers with popups and optionally lets the
 * caller pick a point (incident form). Centred on Tajikistan by default.
 */
export function MapView({ markers = [], center = [69.0, 38.8], zoom = 6, className, onPick }: MapViewProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const mapRef = useRef<maplibregl.Map | null>(null);
    const pickMarkerRef = useRef<maplibregl.Marker | null>(null);
    const onPickRef = useRef(onPick);
    onPickRef.current = onPick;

    useEffect(() => {
        if (!containerRef.current) {
            return;
        }

        const map = new maplibregl.Map({
            container: containerRef.current,
            style: mapStyle,
            center,
            zoom,
        });
        map.addControl(new maplibregl.NavigationControl(), 'top-right');
        mapRef.current = map;

        const handleClick = (event: maplibregl.MapMouseEvent) => {
            if (!onPickRef.current) {
                return;
            }

            const { lng, lat } = event.lngLat;

            if (pickMarkerRef.current) {
                pickMarkerRef.current.setLngLat([lng, lat]);
            } else {
                pickMarkerRef.current = new maplibregl.Marker({ color: '#1f4e8c' }).setLngLat([lng, lat]).addTo(map);
            }

            onPickRef.current({ lat, lng });
        };

        map.on('click', handleClick);

        return () => map.remove();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        const map = mapRef.current;

        if (!map) {
            return;
        }

        const created = markers.map((marker) => {
            const element = document.createElement('div');
            element.style.width = '18px';
            element.style.height = '18px';
            element.style.borderRadius = '9999px';
            element.style.background = marker.color;
            element.style.border = '2px solid #ffffff';
            element.style.boxShadow = '0 0 0 1px rgba(0,0,0,0.25)';
            element.style.cursor = 'pointer';

            const popupContent = document.createElement('div');
            const titleEl = document.createElement('strong');
            titleEl.textContent = marker.title;
            popupContent.appendChild(titleEl);
            marker.lines?.forEach((line) => {
                const lineEl = document.createElement('div');
                lineEl.className = 'text-xs text-muted-foreground';
                lineEl.textContent = line;
                popupContent.appendChild(lineEl);
            });

            return new maplibregl.Marker({ element })
                .setLngLat([marker.lng, marker.lat])
                .setPopup(new maplibregl.Popup({ offset: 14 }).setDOMContent(popupContent))
                .addTo(map);
        });

        return () => created.forEach((marker) => marker.remove());
    }, [markers]);

    return <div ref={containerRef} className={cn('h-full w-full', className)} />;
}

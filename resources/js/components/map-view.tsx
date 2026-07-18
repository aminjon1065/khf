import { usePage } from '@inertiajs/react';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { useEffect, useRef, useState } from 'react';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

export type MapMarker = {
    id: number | string;
    lat: number;
    lng: number;
    color: string;
    title: string;
    lines?: string[];
};

export type MapUnitMarker = {
    id: number | string;
    lat: number;
    lng: number;
    title: string;
};

export type MapLayerVisibility = {
    incidents?: boolean;
    units?: boolean;
    riskZones?: boolean;
};

type MapViewProps = {
    markers?: MapMarker[];
    unitMarkers?: MapUnitMarker[];
    riskZones?: GeoJSON.FeatureCollection;
    layerVisibility?: MapLayerVisibility;
    center?: [number, number];
    zoom?: number;
    className?: string;
    onPick?: (coords: { lat: number; lng: number }) => void;
    initialPickedCoords?: { lat: number | null; lng: number | null } | null;
};

function visibilityMode(enabled?: boolean): 'visible' | 'none' {
    return enabled === false ? 'none' : 'visible';
}

function applyLayerVisibility(
    map: maplibregl.Map,
    layerVisibility?: MapLayerVisibility,
): void {
    const incidentLayers = ['clusters', 'cluster-count', 'unclustered-point'];
    const unitLayers = ['unit-points'];
    const riskLayers = ['risk-zones-fill', 'risk-zones-outline'];

    for (const layerId of incidentLayers) {
        if (map.getLayer(layerId)) {
            map.setLayoutProperty(
                layerId,
                'visibility',
                visibilityMode(layerVisibility?.incidents),
            );
        }
    }

    for (const layerId of unitLayers) {
        if (map.getLayer(layerId)) {
            map.setLayoutProperty(
                layerId,
                'visibility',
                visibilityMode(layerVisibility?.units),
            );
        }
    }

    for (const layerId of riskLayers) {
        if (map.getLayer(layerId)) {
            map.setLayoutProperty(
                layerId,
                'visibility',
                visibilityMode(layerVisibility?.riskZones),
            );
        }
    }
}

function buildMarkerPopup(title: string, lines: string[]): HTMLElement {
    const popupContent = document.createElement('div');
    popupContent.className = 'space-y-0.5';

    const titleEl = document.createElement('strong');
    titleEl.className = 'block text-sm';
    titleEl.textContent = title;
    popupContent.appendChild(titleEl);

    for (const line of lines) {
        if (!line) {
            continue;
        }

        const lineEl = document.createElement('div');
        lineEl.className = 'text-xs text-muted-foreground';
        lineEl.textContent = line;
        popupContent.appendChild(lineEl);
    }

    return popupContent;
}

function parseMarkerLines(rawLines: unknown): string[] {
    if (typeof rawLines === 'string') {
        try {
            const parsed: unknown = JSON.parse(rawLines);

            if (Array.isArray(parsed)) {
                return parsed.map(String);
            }
        } catch {
            return [];
        }
    } else if (Array.isArray(rawLines)) {
        return (rawLines as unknown[]).map(String);
    }

    return [];
}

// Default OSM raster tiles — overridden at runtime from shared Inertia `map` props (ТЗ §10.8).
const defaultTileUrl = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
const defaultGlyphsUrl =
    'https://demotiles.maplibre.org/font/{fontstack}/{range}.pbf';

function buildMapStyle(options: {
    tileUrl: string;
    glyphsUrl: string;
    attribution: string;
    tileSize: number;
}): maplibregl.StyleSpecification {
    return {
        version: 8,
        glyphs: options.glyphsUrl,
        sources: {
            osm: {
                type: 'raster',
                tiles: [options.tileUrl],
                tileSize: options.tileSize,
                attribution: options.attribution,
            },
        },
        layers: [{ id: 'osm', type: 'raster', source: 'osm' }],
    };
}

function isWebGlAvailable(): boolean {
    try {
        const canvas = document.createElement('canvas');

        return Boolean(
            canvas.getContext('webgl2') ||
            canvas.getContext('webgl') ||
            canvas.getContext('experimental-webgl'),
        );
    } catch {
        return false;
    }
}

/**
 * Reusable MapLibre map (ТЗ §6.3). Plots colour-coded markers with popups and optionally lets the
 * caller pick a point (incident form). Centred on Tajikistan by default.
 */
export function MapView({
    markers = [],
    unitMarkers = [],
    riskZones,
    layerVisibility,
    center = [69.0, 38.8],
    zoom = 6,
    className,
    onPick,
    initialPickedCoords,
}: MapViewProps) {
    const { props } = usePage();
    const { t } = useTranslations();
    const mapConfig = props.map;
    const containerRef = useRef<HTMLDivElement>(null);
    const mapRef = useRef<maplibregl.Map | null>(null);
    const pickMarkerRef = useRef<maplibregl.Marker | null>(null);
    const initialPickedLatitude = initialPickedCoords?.lat;
    const initialPickedLongitude = initialPickedCoords?.lng;
    const [failure, setFailure] = useState<'unsupported' | 'error' | null>(
        null,
    );

    useEffect(() => {
        const container = containerRef.current;

        if (!container) {
            return;
        }

        if (!isWebGlAvailable()) {
            const frame = window.requestAnimationFrame(() =>
                setFailure('unsupported'),
            );

            return () => window.cancelAnimationFrame(frame);
        }

        const initialCenter: [number, number] =
            initialPickedCoords &&
            initialPickedCoords.lat &&
            initialPickedCoords.lng
                ? [initialPickedCoords.lng, initialPickedCoords.lat]
                : center;

        let map: maplibregl.Map;

        try {
            map = new maplibregl.Map({
                container,
                style: buildMapStyle({
                    tileUrl: mapConfig?.tileUrl ?? defaultTileUrl,
                    glyphsUrl: mapConfig?.glyphsUrl ?? defaultGlyphsUrl,
                    attribution: mapConfig?.attribution ?? '© OpenStreetMap',
                    tileSize: mapConfig?.tileSize ?? 256,
                }),
                center: initialCenter,
                zoom:
                    initialPickedCoords &&
                    initialPickedCoords.lat &&
                    initialPickedCoords.lng
                        ? 10
                        : zoom,
            });
        } catch {
            const frame = window.requestAnimationFrame(() =>
                setFailure('error'),
            );

            return () => window.cancelAnimationFrame(frame);
        }

        let loaded = false;

        map.addControl(new maplibregl.NavigationControl(), 'top-right');
        map.addControl(new maplibregl.FullscreenControl(), 'top-right');
        map.addControl(
            new maplibregl.GeolocateControl({
                positionOptions: { enableHighAccuracy: true },
                trackUserLocation: true,
            }),
            'top-right',
        );

        map.on('load', () => {
            loaded = true;
            map.resize();
        });

        map.on('error', () => {
            if (!loaded) {
                setFailure('error');
            }
        });

        mapRef.current = map;

        // If in pick mode and initial coordinates are provided, create marker
        if (
            onPick &&
            initialPickedCoords &&
            initialPickedCoords.lat &&
            initialPickedCoords.lng
        ) {
            pickMarkerRef.current = new maplibregl.Marker({
                color: '#1f4e8c',
            })
                .setLngLat([initialPickedCoords.lng, initialPickedCoords.lat])
                .addTo(map);
        }

        return () => {
            if (pickMarkerRef.current) {
                pickMarkerRef.current.remove();
                pickMarkerRef.current = null;
            }

            map.remove();
            mapRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Pan to dynamic coordinate changes
    useEffect(() => {
        const map = mapRef.current;

        if (!map || !initialPickedLatitude || !initialPickedLongitude) {
            return;
        }

        map.flyTo({
            center: [initialPickedLongitude, initialPickedLatitude],
            zoom: map.getZoom() < 8 ? 8 : map.getZoom(),
            essential: true,
        });

        if (pickMarkerRef.current) {
            pickMarkerRef.current.setLngLat([
                initialPickedLongitude,
                initialPickedLatitude,
            ]);
        } else {
            pickMarkerRef.current = new maplibregl.Marker({
                color: '#1f4e8c',
            })
                .setLngLat([initialPickedLongitude, initialPickedLatitude])
                .addTo(map);
        }
    }, [initialPickedLatitude, initialPickedLongitude]);

    // Handle incoming markers via GeoJSON and Clustering
    useEffect(() => {
        const map = mapRef.current;

        if (!map) {
            return;
        }

        const loadData = () => {
            const geojsonData: GeoJSON.FeatureCollection = {
                type: 'FeatureCollection',
                features: markers.map((m) => ({
                    type: 'Feature',
                    geometry: { type: 'Point', coordinates: [m.lng, m.lat] },
                    properties: { ...m },
                })),
            };

            const source = map.getSource('incidents');

            if (source) {
                (source as maplibregl.GeoJSONSource).setData(geojsonData);
            } else {
                map.addSource('incidents', {
                    type: 'geojson',
                    data: geojsonData,
                    cluster: true,
                    clusterMaxZoom: 14,
                    clusterRadius: 50,
                });

                map.addLayer({
                    id: 'clusters',
                    type: 'circle',
                    source: 'incidents',
                    filter: ['has', 'point_count'],
                    paint: {
                        'circle-color': [
                            'step',
                            ['get', 'point_count'],
                            '#3b82f6', // blue-500
                            10,
                            '#eab308', // yellow-500
                            50,
                            '#ef4444', // red-500
                        ],
                        'circle-radius': [
                            'step',
                            ['get', 'point_count'],
                            15,
                            10,
                            20,
                            50,
                            25,
                        ],
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#fff',
                    },
                });

                map.addLayer({
                    id: 'cluster-count',
                    type: 'symbol',
                    source: 'incidents',
                    filter: ['has', 'point_count'],
                    layout: {
                        'text-field': '{point_count_abbreviated}',
                        'text-font': ['sans-serif'],
                        'text-size': 12,
                    },
                    paint: {
                        'text-color': '#ffffff',
                    },
                });

                map.addLayer({
                    id: 'unclustered-point',
                    type: 'circle',
                    source: 'incidents',
                    filter: ['!', ['has', 'point_count']],
                    paint: {
                        'circle-color': ['get', 'color'],
                        'circle-radius': 8,
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#fff',
                    },
                });

                // Interaction
                map.on('click', 'clusters', (e) => {
                    const features = map.queryRenderedFeatures(e.point, {
                        layers: ['clusters'],
                    });
                    const clusterId = features[0].properties.cluster_id;
                    const source = map.getSource(
                        'incidents',
                    ) as maplibregl.GeoJSONSource;

                    source
                        .getClusterExpansionZoom(clusterId)
                        .then((expansionZoom) => {
                            const geom = features[0].geometry as GeoJSON.Point;
                            map.easeTo({
                                center: geom.coordinates as [number, number],
                                zoom: expansionZoom,
                            });
                        })
                        .catch(() => {
                            // Ignore cluster expansion failures.
                        });
                });

                map.on('click', 'unclustered-point', (e) => {
                    if (!e.features || !e.features[0]) {
                        return;
                    }

                    const coordinates = (
                        e.features[0].geometry as GeoJSON.Point
                    ).coordinates.slice() as [number, number];
                    const props = e.features[0].properties as Record<
                        string,
                        unknown
                    >;

                    const lines = parseMarkerLines(props.lines);

                    new maplibregl.Popup({ offset: 10 })
                        .setLngLat(coordinates)
                        .setDOMContent(
                            buildMarkerPopup(String(props.title ?? ''), lines),
                        )
                        .addTo(map);
                });

                map.on('mouseenter', 'clusters', () => {
                    map.getCanvas().style.cursor = 'pointer';
                });
                map.on('mouseleave', 'clusters', () => {
                    map.getCanvas().style.cursor = '';
                });
                map.on('mouseenter', 'unclustered-point', () => {
                    map.getCanvas().style.cursor = 'pointer';
                });
                map.on('mouseleave', 'unclustered-point', () => {
                    map.getCanvas().style.cursor = '';
                });
            }
        };

        if (map.isStyleLoaded()) {
            loadData();
            applyLayerVisibility(map, layerVisibility);
        } else {
            map.once('style.load', () => {
                loadData();
                applyLayerVisibility(map, layerVisibility);
            });
        }
    }, [markers, layerVisibility]);

    useEffect(() => {
        const map = mapRef.current;

        if (!map) {
            return;
        }

        const loadUnits = () => {
            const geojsonData: GeoJSON.FeatureCollection = {
                type: 'FeatureCollection',
                features: unitMarkers.map((unit) => ({
                    type: 'Feature',
                    geometry: {
                        type: 'Point',
                        coordinates: [unit.lng, unit.lat],
                    },
                    properties: { ...unit },
                })),
            };

            const source = map.getSource('units');

            if (source) {
                (source as maplibregl.GeoJSONSource).setData(geojsonData);
            } else {
                map.addSource('units', {
                    type: 'geojson',
                    data: geojsonData,
                });

                map.addLayer({
                    id: 'unit-points',
                    type: 'circle',
                    source: 'units',
                    paint: {
                        'circle-color': '#1f4e8c',
                        'circle-radius': 9,
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#fff',
                    },
                });

                map.on('click', 'unit-points', (e) => {
                    if (!e.features || !e.features[0]) {
                        return;
                    }

                    const coordinates = (
                        e.features[0].geometry as GeoJSON.Point
                    ).coordinates.slice() as [number, number];
                    const props = e.features[0].properties as Record<
                        string,
                        unknown
                    >;

                    new maplibregl.Popup({ offset: 10 })
                        .setLngLat(coordinates)
                        .setDOMContent(
                            buildMarkerPopup(String(props.title ?? ''), []),
                        )
                        .addTo(map);
                });

                map.on('mouseenter', 'unit-points', () => {
                    map.getCanvas().style.cursor = 'pointer';
                });
                map.on('mouseleave', 'unit-points', () => {
                    map.getCanvas().style.cursor = '';
                });
            }

            applyLayerVisibility(map, layerVisibility);
        };

        if (map.isStyleLoaded()) {
            loadUnits();
        } else {
            map.once('style.load', loadUnits);
        }
    }, [unitMarkers, layerVisibility]);

    useEffect(() => {
        const map = mapRef.current;

        if (!map || !riskZones) {
            return;
        }

        const loadRiskZones = () => {
            const source = map.getSource('risk-zones');

            if (source) {
                (source as maplibregl.GeoJSONSource).setData(riskZones);
            } else {
                map.addSource('risk-zones', {
                    type: 'geojson',
                    data: riskZones,
                });

                map.addLayer(
                    {
                        id: 'risk-zones-fill',
                        type: 'fill',
                        source: 'risk-zones',
                        paint: {
                            'fill-color': ['get', 'color'],
                            'fill-opacity': 0.25,
                        },
                    },
                    map.getLayer('clusters') ? 'clusters' : undefined,
                );

                map.addLayer(
                    {
                        id: 'risk-zones-outline',
                        type: 'line',
                        source: 'risk-zones',
                        paint: {
                            'line-color': ['get', 'color'],
                            'line-width': 2,
                            'line-opacity': 0.8,
                        },
                    },
                    map.getLayer('clusters') ? 'clusters' : undefined,
                );

                map.on('click', 'risk-zones-fill', (e) => {
                    if (!e.features || !e.features[0]) {
                        return;
                    }

                    const coordinates = e.lngLat.toArray() as [number, number];
                    const props = e.features[0].properties as Record<
                        string,
                        unknown
                    >;

                    new maplibregl.Popup({ offset: 10 })
                        .setLngLat(coordinates)
                        .setDOMContent(
                            buildMarkerPopup(String(props.name ?? ''), []),
                        )
                        .addTo(map);
                });

                map.on('mouseenter', 'risk-zones-fill', () => {
                    map.getCanvas().style.cursor = 'pointer';
                });
                map.on('mouseleave', 'risk-zones-fill', () => {
                    map.getCanvas().style.cursor = '';
                });
            }

            applyLayerVisibility(map, layerVisibility);
        };

        if (map.isStyleLoaded()) {
            loadRiskZones();
        } else {
            map.once('style.load', loadRiskZones);
        }
    }, [riskZones, layerVisibility]);

    useEffect(() => {
        const map = mapRef.current;

        if (!map || !map.isStyleLoaded()) {
            return;
        }

        applyLayerVisibility(map, layerVisibility);
    }, [layerVisibility]);

    useEffect(() => {
        const map = mapRef.current;

        if (!map || !onPick) {
            return;
        }

        const handler = (event: maplibregl.MapMouseEvent) => {
            const { lng, lat } = event.lngLat;

            if (pickMarkerRef.current) {
                pickMarkerRef.current.setLngLat([lng, lat]);
            } else {
                pickMarkerRef.current = new maplibregl.Marker({
                    color: '#1f4e8c',
                })
                    .setLngLat([lng, lat])
                    .addTo(map);
            }

            onPick({ lat, lng });
        };

        map.on('click', handler);

        return () => {
            map.off('click', handler);
        };
    }, [onPick]);

    return (
        <div className={cn('relative h-full w-full', className)}>
            {failure !== null && (
                <div
                    role="status"
                    className="absolute inset-0 z-10 flex items-center justify-center bg-muted/80 p-6 text-center"
                >
                    <div className="max-w-md space-y-2">
                        <p className="text-sm font-medium text-foreground">
                            {t('map.unavailable_title')}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {failure === 'unsupported'
                                ? t('map.unavailable_webgl')
                                : t('map.unavailable_error')}
                        </p>
                    </div>
                </div>
            )}
            <div ref={containerRef} className="h-full w-full" />
        </div>
    );
}

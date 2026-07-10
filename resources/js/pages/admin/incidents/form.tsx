import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import {
    CpLocaleTabs,
    CpPanel,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import InputError from '@/components/input-error';
import { MapView } from '@/components/map-view';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/incidents';
import type {
    BlueprintDefinition,
    BlueprintFieldOptions,
    RelationOption,
} from '@/types/cms';

type Translation = { title: string; description: string };
type LocaleOption = { code: string; native_name: string };
type RegionCoordinateOption = RelationOption & {
    lat?: number | null;
    lng?: number | null;
};

type IncidentData = {
    id: number;
    type: string;
    hazard_level: string;
    status: string;
    region_id: number | null;
    latitude: number | null;
    longitude: number | null;
    occurred_at: string | null;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    incident: IncidentData | null;
    blueprint: BlueprintDefinition;
    fieldOptions: BlueprintFieldOptions;
    locales: LocaleOption[];
    defaultLocale: string;
};

function IncidentLocationPanel({
    latitude,
    longitude,
    errors,
    regionCoordinates,
    onLatitudeChange,
    onLongitudeChange,
    onPick,
}: {
    latitude: number | '';
    longitude: number | '';
    errors: Record<string, string>;
    regionCoordinates: Record<number, { lat: number; lng: number }>;
    onLatitudeChange: (value: number | '') => void;
    onLongitudeChange: (value: number | '') => void;
    onPick: (coords: { lat: number; lng: number }) => void;
}) {
    return (
        <CpPanel
            title="Координаты на карте"
            description="Кликните по карте или выберите регион для приближения."
        >
            <div className="relative h-64 overflow-hidden rounded-lg border border-border">
                <MapView
                    initialPickedCoords={
                        latitude && longitude
                            ? {
                                  lat: Number(latitude),
                                  lng: Number(longitude),
                              }
                            : null
                    }
                    onPick={onPick}
                />
            </div>
            <div className="grid grid-cols-2 gap-3">
                <div className="space-y-2">
                    <Label htmlFor="latitude">Широта</Label>
                    <Input
                        id="latitude"
                        type="number"
                        step="0.0000001"
                        value={latitude}
                        onChange={(event) =>
                            onLatitudeChange(
                                event.target.value === ''
                                    ? ''
                                    : Number(event.target.value),
                            )
                        }
                    />
                    <InputError message={errors.latitude} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="longitude">Долгота</Label>
                    <Input
                        id="longitude"
                        type="number"
                        step="0.0000001"
                        value={longitude}
                        onChange={(event) =>
                            onLongitudeChange(
                                event.target.value === ''
                                    ? ''
                                    : Number(event.target.value),
                            )
                        }
                    />
                    <InputError message={errors.longitude} />
                </div>
            </div>
            <p className="text-xs text-muted-foreground">
                Регионов с координатами для авто-подстановки: {' '}
                {Object.keys(regionCoordinates).length}
            </p>
        </CpPanel>
    );
}

export default function IncidentForm({
    incident,
    blueprint,
    fieldOptions,
    locales,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(incident);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = incident?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            description: existing?.description ?? '',
        };
    });

    const form = useForm({
        type: incident?.type ?? '',
        hazard_level: incident?.hazard_level ?? '',
        status: incident?.status ?? 'active',
        region_id: incident?.region_id ?? null,
        latitude: incident?.latitude ?? '',
        longitude: incident?.longitude ?? '',
        occurred_at: incident?.occurred_at ?? '',
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;
    const regionCoordinates = (
        (fieldOptions.region_id as RegionCoordinateOption[] | undefined) ?? []
    ).reduce(
        (acc, region) => {
            if (region.lat != null && region.lng != null) {
                acc[region.id] = { lat: region.lat, lng: region.lng };
            }

            return acc;
        },
        {} as Record<number, { lat: number; lng: number }>,
    );

    const onRootChange = (handle: string, value: unknown) => {
        if (handle !== 'region_id') {
            form.setData(handle as keyof typeof form.data, value as never);
            return;
        }

        const regionId =
            value === null || value === '' ? null : Number(value);
        const selectedRegionCoordinates = regionId
            ? regionCoordinates[regionId]
            : undefined;

        if (selectedRegionCoordinates) {
            form.setData((current) => ({
                ...current,
                region_id: regionId,
                latitude: Number(selectedRegionCoordinates.lat.toFixed(7)),
                longitude: Number(selectedRegionCoordinates.lng.toFixed(7)),
            }));
            return;
        }

        form.setData('region_id', regionId);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && incident) {
            form.put(update(incident.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const title = isEdit ? 'Редактирование события' : 'Новое событие ЧС';
    const formMeta = {
        statuses: [],
        statusTransitions: [],
    };

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                modelInfo={{ type: 'incident', id: incident?.id ?? null }}
                sidebar={
                    <>
                        <CpBlueprintForm
                            blueprint={blueprint}
                            section="sidebar"
                            data={form.data}
                            errors={errors}
                            activeLocale={activeLocale}
                            fieldOptions={fieldOptions}
                            meta={formMeta}
                            excludeHandles={['latitude', 'longitude']}
                            onRootChange={onRootChange}
                            onTranslationChange={(locale, handle, value) =>
                                form.setData('translations', {
                                    ...form.data.translations,
                                    [locale]: {
                                        ...form.data.translations[locale],
                                        [handle]: value,
                                    },
                                })
                            }
                            onAssetChange={(patch) =>
                                form.setData({ ...form.data, ...patch })
                            }
                        />

                        <IncidentLocationPanel
                            latitude={form.data.latitude}
                            longitude={form.data.longitude}
                            errors={errors}
                            regionCoordinates={regionCoordinates}
                            onLatitudeChange={(value) =>
                                form.setData('latitude', value)
                            }
                            onLongitudeChange={(value) =>
                                form.setData('longitude', value)
                            }
                            onPick={({ lat, lng }) => {
                                form.setData((data) => ({
                                    ...data,
                                    latitude: Number(lat.toFixed(7)),
                                    longitude: Number(lng.toFixed(7)),
                                }));
                            }}
                        />
                    </>
                }
            >
                <CpLocaleTabs
                    locales={locales}
                    active={activeLocale}
                    onChange={setActiveLocale}
                    isComplete={(code) =>
                        Boolean(form.data.translations[code]?.title)
                    }
                />

                <CpBlueprintForm
                    blueprint={blueprint}
                    section="main"
                    data={form.data}
                    errors={errors}
                    activeLocale={activeLocale}
                    fieldOptions={fieldOptions}
                    meta={formMeta}
                    titleAsHeader
                    titleFieldHandle="title"
                    onRootChange={onRootChange}
                    onTranslationChange={(locale, handle, value) =>
                        form.setData('translations', {
                            ...form.data.translations,
                            [locale]: {
                                ...form.data.translations[locale],
                                [handle]: value,
                            },
                        })
                    }
                    onAssetChange={(patch) =>
                        form.setData({ ...form.data, ...patch })
                    }
                />
            </CpPublishForm>
        </>
    );
}

IncidentForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'События ЧС', href: index() },
    ],
};

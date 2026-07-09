import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import {
    CpLocaleTabs,
    CpPanel,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import InputError from '@/components/input-error';
import { MapView } from '@/components/map-view';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/incidents';

type Translation = { title: string; description: string };
type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };
type RegionOption = { id: number; name: string; lat: number; lng: number };

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
    types: Option[];
    levels: Option[];
    statuses: Option[];
    regions: RegionOption[];
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function IncidentForm({
    incident,
    types,
    levels,
    statuses,
    regions,
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
        type: incident?.type ?? types[0]?.value ?? '',
        hazard_level: incident?.hazard_level ?? levels[0]?.value ?? '',
        status: incident?.status ?? statuses[0]?.value ?? 'active',
        region_id: incident?.region_id ?? null,
        latitude: incident?.latitude ?? '',
        longitude: incident?.longitude ?? '',
        occurred_at: incident?.occurred_at ?? '',
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;

    const setTranslation = (
        locale: string,
        field: keyof Translation,
        value: string,
    ) => {
        form.setData('translations', {
            ...form.data.translations,
            [locale]: { ...form.data.translations[locale], [field]: value },
        });
    };

    const onRegionChange = (value: string) => {
        const regionId = value === 'none' ? null : Number(value);

        if (regionId) {
            const region = regions.find((r) => r.id === regionId);

            if (region && region.lat && region.lng) {
                form.setData((prev) => ({
                    ...prev,
                    region_id: regionId,
                    latitude: Number(region.lat.toFixed(7)),
                    longitude: Number(region.lng.toFixed(7)),
                }));
            } else {
                form.setData('region_id', regionId);
            }
        } else {
            form.setData('region_id', null);
        }
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && incident) {
            form.put(update(incident.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование события' : 'Новое событие ЧС';

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
                        <CpPanel title="Параметры">
                            <div className="space-y-2">
                                <Label htmlFor="type">Тип</Label>
                                <Select
                                    value={form.data.type}
                                    onValueChange={(value) =>
                                        form.setData('type', value)
                                    }
                                >
                                    <SelectTrigger id="type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((type) => (
                                            <SelectItem
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="hazard_level">
                                    Уровень опасности
                                </Label>
                                <Select
                                    value={form.data.hazard_level}
                                    onValueChange={(value) =>
                                        form.setData('hazard_level', value)
                                    }
                                >
                                    <SelectTrigger id="hazard_level">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {levels.map((level) => (
                                            <SelectItem
                                                key={level.value}
                                                value={level.value}
                                            >
                                                {level.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.hazard_level} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="status">Статус</Label>
                                <Select
                                    value={form.data.status}
                                    onValueChange={(value) =>
                                        form.setData('status', value)
                                    }
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((status) => (
                                            <SelectItem
                                                key={status.value}
                                                value={status.value}
                                            >
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="region">Регион</Label>
                                <Select
                                    value={
                                        form.data.region_id
                                            ? String(form.data.region_id)
                                            : 'none'
                                    }
                                    onValueChange={onRegionChange}
                                >
                                    <SelectTrigger id="region">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            — Не указан —
                                        </SelectItem>
                                        {regions.map((region) => (
                                            <SelectItem
                                                key={region.id}
                                                value={String(region.id)}
                                            >
                                                {region.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.region_id} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="occurred_at">
                                    Дата и время
                                </Label>
                                <Input
                                    id="occurred_at"
                                    type="datetime-local"
                                    value={form.data.occurred_at}
                                    onChange={(event) =>
                                        form.setData(
                                            'occurred_at',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.occurred_at} />
                            </div>
                        </CpPanel>

                        <CpPanel
                            title="Координаты на карте"
                            description="Кликните по карте или выберите регион для приближения."
                        >
                            <div className="relative h-64 overflow-hidden rounded-lg border border-border">
                                <MapView
                                    initialPickedCoords={
                                        form.data.latitude &&
                                        form.data.longitude
                                            ? {
                                                  lat: Number(
                                                      form.data.latitude,
                                                  ),
                                                  lng: Number(
                                                      form.data.longitude,
                                                  ),
                                              }
                                            : null
                                    }
                                    onPick={({ lat, lng }) => {
                                        form.setData((data) => ({
                                            ...data,
                                            latitude: Number(lat.toFixed(7)),
                                            longitude: Number(lng.toFixed(7)),
                                        }));
                                    }}
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-2">
                                    <Label htmlFor="latitude">Широта</Label>
                                    <Input
                                        id="latitude"
                                        type="number"
                                        step="0.0000001"
                                        value={form.data.latitude}
                                        onChange={(event) =>
                                            form.setData(
                                                'latitude',
                                                event.target.value === ''
                                                    ? ''
                                                    : Number(
                                                          event.target.value,
                                                      ),
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
                                        value={form.data.longitude}
                                        onChange={(event) =>
                                            form.setData(
                                                'longitude',
                                                event.target.value === ''
                                                    ? ''
                                                    : Number(
                                                          event.target.value,
                                                      ),
                                            )
                                        }
                                    />
                                    <InputError message={errors.longitude} />
                                </div>
                            </div>
                        </CpPanel>
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

                <div>
                    <input
                        aria-label="Заголовок"
                        value={active.title}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'title',
                                event.target.value,
                            )
                        }
                        placeholder="Заголовок события"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={errors[`translations.${activeLocale}.title`]}
                    />
                </div>

                <CpPanel title="Описание">
                    <div className="space-y-2">
                        <Label htmlFor="description">Описание</Label>
                        <Textarea
                            id="description"
                            rows={8}
                            value={active.description}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'description',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                errors[
                                    `translations.${activeLocale}.description`
                                ]
                            }
                        />
                    </div>
                </CpPanel>
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

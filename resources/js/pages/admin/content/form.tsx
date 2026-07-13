import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AlertPublishConfirmDialog } from '@/components/admin/cp/alert-publish-dialog';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import { IncidentLocationPanel } from '@/components/admin/cp/incident-location-panel';
import type { RegionCoordinatesMap } from '@/components/admin/cp/incident-location-panel';
import {
    CpPollOptionsField,
    emptyPollOption,
} from '@/components/admin/cp/poll-options-field';
import type { PollOptionRow } from '@/components/admin/cp/poll-options-field';
import {
    CpLocaleTabs,
    CpPanel,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { hub as contentHub } from '@/routes/admin/content';
import type {
    BlueprintDefinition,
    BlueprintFieldDefinition,
    BlueprintFieldOptions,
    ExistingAssetFile,
    ExistingPhoto,
    SelectOption,
} from '@/types/cms';

type LocaleOption = { code: string; native_name: string };

type ContentTypeMeta = {
    handle: string;
    label: string;
    titleField: string;
    features: string[];
};

type EntryUrls = {
    back: string;
    store: string;
    update: string | null;
    estimate?: string;
};

type EntryData = {
    id: number;
    status?: string;
    translations?: Record<string, Record<string, unknown>>;
    options?: PollOptionRow[];
    total_votes?: number;
} & Record<string, unknown>;

type PageProps = {
    entry: EntryData | null;
    contentType: ContentTypeMeta;
    urls: EntryUrls;
    blueprint: BlueprintDefinition;
    fieldOptions: BlueprintFieldOptions;
    locales: LocaleOption[];
    statuses: SelectOption[];
    statusTransitions: SelectOption[];
    defaultLocale: string;
    existingFiles?: ExistingAssetFile[];
    existingPhotos?: ExistingPhoto[];
    photoUrl?: string | null;
    coverUrl?: string | null;
    regionCoordinates?: RegionCoordinatesMap;
};

function blueprintFields(
    blueprint: BlueprintDefinition,
): BlueprintFieldDefinition[] {
    return Object.values(blueprint.sections).flatMap(
        (section) => section.fields,
    );
}

function isPollOptionsField(field: BlueprintFieldDefinition): boolean {
    return field.handle === 'options' && field.type === 'replicator';
}

function hasIncidentLocationFields(
    fields: BlueprintFieldDefinition[],
): boolean {
    const handles = new Set(fields.map((field) => field.handle));

    return handles.has('latitude') && handles.has('longitude');
}

function emptyValueFor(field: BlueprintFieldDefinition): unknown {
    if (field.type === 'toggle') {
        return field.handle === 'is_dismissible';
    }

    if (field.type === 'entries') {
        return field.max === 1 ? null : [];
    }

    if (field.handle === 'latitude' || field.handle === 'longitude') {
        return '';
    }

    if (field.type === 'number') {
        return field.min ?? null;
    }

    return '';
}

function normalizeRegionCoordinates(
    raw?: RegionCoordinatesMap | Record<string, { lat: number; lng: number }>,
): RegionCoordinatesMap {
    if (!raw) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(raw).map(([id, coords]) => [Number(id), coords]),
    );
}

function defaultStatusValue(
    fieldOptions: BlueprintFieldOptions,
    statuses: SelectOption[],
): string {
    const statusOptions = fieldOptions.status;

    if (
        Array.isArray(statusOptions) &&
        statusOptions.length > 0 &&
        'value' in statusOptions[0]
    ) {
        return String(statusOptions[0].value);
    }

    return statuses[0]?.value ?? 'draft';
}

function applyAssetDefaults(
    field: BlueprintFieldDefinition,
    rootDefaults: Record<string, unknown>,
): void {
    if (field.handle === 'files') {
        rootDefaults.files = [];
        rootDefaults.remove_files = [];

        return;
    }

    if (field.handle === 'photos') {
        rootDefaults.photos = [];
        rootDefaults.remove_photos = [];

        return;
    }

    if (field.handle === 'photo') {
        rootDefaults.photo = null;
        rootDefaults.remove_photo = false;

        return;
    }

    rootDefaults.cover = null;
    rootDefaults.cover_media_id = null;
    rootDefaults.remove_cover = false;
}

function normalizePollOptions(
    entry: EntryData | null,
    locales: LocaleOption[],
): PollOptionRow[] {
    const existing = entry?.options;

    if (Array.isArray(existing) && existing.length > 0) {
        return existing.map((option, index) => ({
            id: option.id,
            sort_order: option.sort_order ?? index,
            votes_count: option.votes_count,
            translations: locales.reduce(
                (acc, locale) => {
                    acc[locale.code] = {
                        label: option.translations?.[locale.code]?.label ?? '',
                    };

                    return acc;
                },
                {} as Record<string, { label: string }>,
            ),
        }));
    }

    return [emptyPollOption(locales, 0), emptyPollOption(locales, 1)];
}

function buildInitialFormData(
    blueprint: BlueprintDefinition,
    entry: EntryData | null,
    locales: LocaleOption[],
    statuses: SelectOption[],
    fieldOptions: BlueprintFieldOptions,
): Record<string, unknown> {
    const fields = blueprintFields(blueprint);
    const rootDefaults: Record<string, unknown> = {};
    const translationDefaults: Record<string, unknown> = {};
    const hasPollOptions = fields.some(isPollOptionsField);

    for (const field of fields) {
        if (isPollOptionsField(field)) {
            continue;
        }

        if (field.localizable) {
            translationDefaults[field.handle] = '';
        } else if (field.type === 'assets') {
            applyAssetDefaults(field, rootDefaults);
        } else {
            rootDefaults[field.handle] = emptyValueFor(field);
        }
    }

    const root: Record<string, unknown> = { ...rootDefaults };

    if (entry) {
        for (const [key, value] of Object.entries(entry)) {
            if (
                key === 'id' ||
                key === 'translations' ||
                key === 'total_votes' ||
                key === 'options'
            ) {
                continue;
            }

            if (
                (key === 'latitude' || key === 'longitude') &&
                (value === null || value === undefined)
            ) {
                root[key] = '';
                continue;
            }

            root[key] = value ?? rootDefaults[key] ?? null;
        }
    } else if (!root.status) {
        root.status = defaultStatusValue(fieldOptions, statuses);
    }

    if (hasPollOptions) {
        root.options = normalizePollOptions(entry, locales);

        if (!entry) {
            root.show_results = true;
            root.sort_order = root.sort_order ?? 0;
        }
    }

    const translations: Record<string, Record<string, unknown>> = {};

    for (const locale of locales) {
        translations[locale.code] = {
            ...translationDefaults,
            ...(entry?.translations?.[locale.code] ?? {}),
        };
    }

    return {
        ...root,
        translations,
    };
}

export default function ContentEntryForm({
    entry,
    contentType,
    urls,
    blueprint,
    fieldOptions,
    locales,
    statuses,
    statusTransitions,
    defaultLocale,
    existingFiles,
    existingPhotos,
    photoUrl,
    coverUrl,
    regionCoordinates: regionCoordinatesProp,
}: PageProps) {
    const isEdit = Boolean(entry?.id);
    const isAlert = contentType.handle === 'alert';
    const fields = blueprintFields(blueprint);
    const hasPollOptions = fields.some(isPollOptionsField);
    const hasIncidentLocation = hasIncidentLocationFields(fields);
    const regionCoordinates = normalizeRegionCoordinates(regionCoordinatesProp);
    const form = useForm<Record<string, any>>(
        buildInitialFormData(
            blueprint,
            entry,
            locales,
            statuses,
            fieldOptions,
        ) as Record<string, any>,
    );
    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const [showPublishConfirm, setShowPublishConfirm] = useState(false);
    const [estimatedCount, setEstimatedCount] = useState<number | null>(null);
    const [isEstimating, setIsEstimating] = useState(false);
    const errors = form.errors as Record<string, string>;
    const titleField = contentType.titleField;
    const showSchedule = contentType.features.includes('schedulable');
    const excludeHandles = [
        ...(hasPollOptions ? ['options'] : []),
        ...(hasIncidentLocation ? ['latitude', 'longitude'] : []),
    ];

    const onRootChange = (handle: string, value: unknown) => {
        if (handle !== 'region_id' || !hasIncidentLocation) {
            form.setData(handle, value as never);

            return;
        }

        const regionId = value === null || value === '' ? null : Number(value);
        const selected = regionId ? regionCoordinates[regionId] : undefined;

        if (selected) {
            form.setData({
                ...form.data,
                region_id: regionId,
                latitude: Number(selected.lat.toFixed(7)),
                longitude: Number(selected.lng.toFixed(7)),
            });

            return;
        }

        form.setData('region_id', regionId);
    };

    const doSubmit = () => {
        if (isEdit && urls.update) {
            form.put(urls.update, { preserveScroll: true });
        } else {
            form.post(urls.store, { preserveScroll: true });
        }

        setShowPublishConfirm(false);
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();

        const estimateUrl = urls.estimate;
        const publishingAlert =
            isAlert &&
            form.data.status === 'published' &&
            entry?.status !== 'published' &&
            estimateUrl;

        if (publishingAlert) {
            setIsEstimating(true);
            setShowPublishConfirm(true);

            try {
                const url = new URL(estimateUrl, window.location.origin);
                const regionId = form.data.region_id;

                if (regionId) {
                    url.searchParams.set('region_id', String(regionId));
                }

                const response = await fetch(url.toString());
                const data = (await response.json()) as { count?: number };
                setEstimatedCount(data.count ?? 0);
            } catch {
                setEstimatedCount(0);
            } finally {
                setIsEstimating(false);
            }

            return;
        }

        doSubmit();
    };

    const title = isEdit
        ? `Редактирование — ${contentType.label}`
        : `${contentType.label} — создание`;

    const formMeta = {
        statuses,
        statusTransitions,
        showSchedule,
        existingFiles,
        existingPhotos,
        photoUrl,
        coverUrl,
    };

    const translations = form.data.translations as Record<
        string,
        Record<string, unknown>
    >;

    const latitude =
        form.data.latitude === null || form.data.latitude === undefined
            ? ''
            : (form.data.latitude as number | '');
    const longitude =
        form.data.longitude === null || form.data.longitude === undefined
            ? ''
            : (form.data.longitude as number | '');

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={urls.back}
                onSubmit={submit}
                processing={form.processing}
                saveLabel={isEdit ? 'Обновить' : 'Создать'}
                modelInfo={{
                    type: contentType.handle,
                    id: entry?.id ?? null,
                }}
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
                            excludeHandles={excludeHandles}
                            onRootChange={onRootChange}
                            onTranslationChange={(locale, handle, value) =>
                                form.setData('translations', {
                                    ...translations,
                                    [locale]: {
                                        ...translations[locale],
                                        [handle]: value,
                                    },
                                })
                            }
                            onAssetChange={(patch) =>
                                form.setData({ ...form.data, ...patch })
                            }
                        />

                        {hasIncidentLocation ? (
                            <IncidentLocationPanel
                                latitude={latitude}
                                longitude={longitude}
                                errors={errors}
                                regionCoordinates={regionCoordinates}
                                onLatitudeChange={(value) =>
                                    form.setData('latitude', value)
                                }
                                onLongitudeChange={(value) =>
                                    form.setData('longitude', value)
                                }
                                onPick={({ lat, lng }) => {
                                    form.setData({
                                        ...form.data,
                                        latitude: Number(lat.toFixed(7)),
                                        longitude: Number(lng.toFixed(7)),
                                    });
                                }}
                            />
                        ) : null}
                    </>
                }
            >
                <CpLocaleTabs
                    locales={locales}
                    active={activeLocale}
                    onChange={setActiveLocale}
                    isComplete={(code) =>
                        Boolean(translations[code]?.[titleField])
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
                    titleFieldHandle={titleField}
                    excludeHandles={excludeHandles}
                    onRootChange={onRootChange}
                    onTranslationChange={(locale, handle, value) =>
                        form.setData('translations', {
                            ...translations,
                            [locale]: {
                                ...translations[locale],
                                [handle]: value,
                            },
                        })
                    }
                    onAssetChange={(patch) =>
                        form.setData({ ...form.data, ...patch })
                    }
                />

                {hasPollOptions ? (
                    <CpPanel title="Варианты ответа">
                        <CpPollOptionsField
                            locales={locales}
                            activeLocale={activeLocale}
                            options={
                                (form.data.options as PollOptionRow[]) ?? []
                            }
                            totalVotes={
                                typeof entry?.total_votes === 'number'
                                    ? entry.total_votes
                                    : undefined
                            }
                            errors={errors}
                            onChange={(options) =>
                                form.setData('options', options)
                            }
                        />
                    </CpPanel>
                ) : null}
            </CpPublishForm>

            {isAlert ? (
                <AlertPublishConfirmDialog
                    open={showPublishConfirm}
                    onOpenChange={setShowPublishConfirm}
                    estimatedCount={estimatedCount}
                    isEstimating={isEstimating}
                    isProcessing={form.processing}
                    onConfirm={doSubmit}
                />
            ) : null}
        </>
    );
}

ContentEntryForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Коллекции', href: contentHub() },
        { title: 'Запись', href: '#' },
    ],
};

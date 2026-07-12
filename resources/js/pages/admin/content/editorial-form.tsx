import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import { CpContentHelp } from '@/components/admin/cp/content-help';
import { PostEditorHelp } from '@/components/admin/cp/content-help-topics';
import { CpLivePreview } from '@/components/admin/cp/live-preview';
import {
    CpLocaleTabs,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { CpViewOnSite } from '@/components/admin/cp/view-on-site';
import { CpWorkingCopyBanner } from '@/components/admin/cp/working-copy-banner';
import { useAutosave } from '@/hooks/use-autosave';
import { dashboard } from '@/routes/admin';
import { hub as contentHub } from '@/routes/admin/content';
import type {
    BlueprintDefinition,
    BlueprintFieldDefinition,
    BlueprintFieldOptions,
    BlockSetDefinition,
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
    autosave?: string;
    publishVersion?: string;
};

type EntryData = {
    id: number;
    status?: string;
    cover_url?: string | null;
    translations?: Record<string, Record<string, unknown>>;
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
    coverUrl?: string | null;
    blockset?: BlockSetDefinition;
    publicUrls?: Record<string, string>;
    previewUrls?: Record<string, string>;
    hasUnpublishedChanges?: boolean;
    canPublish?: boolean;
};

const AUTOSAVE_EXCLUDED_KEYS = new Set([
    'status',
    'cover',
    'cover_media_id',
    'remove_cover',
    'files',
    'remove_files',
    'photos',
    'remove_photos',
    'photo',
    'remove_photo',
]);

function blueprintFields(
    blueprint: BlueprintDefinition,
): BlueprintFieldDefinition[] {
    return Object.values(blueprint.sections).flatMap(
        (section) => section.fields,
    );
}

function emptyValueFor(field: BlueprintFieldDefinition): unknown {
    if (field.type === 'toggle') {
        return false;
    }

    if (field.type === 'entries') {
        return field.max === 1 ? null : [];
    }

    if (field.type === 'number') {
        return field.min ?? null;
    }

    if (field.type === 'blocks') {
        return [];
    }

    return '';
}

function emptyTranslationValue(field: BlueprintFieldDefinition): unknown {
    if (field.type === 'blocks') {
        return [];
    }

    return '';
}

function applyAssetDefaults(
    field: BlueprintFieldDefinition,
    rootDefaults: Record<string, unknown>,
): void {
    if (field.handle === 'cover') {
        rootDefaults.cover = null;
        rootDefaults.cover_media_id = null;
        rootDefaults.remove_cover = false;
    }
}

function buildInitialFormData(
    blueprint: BlueprintDefinition,
    entry: EntryData | null,
    locales: LocaleOption[],
    statuses: SelectOption[],
): Record<string, unknown> {
    const fields = blueprintFields(blueprint);
    const rootDefaults: Record<string, unknown> = {};
    const translationDefaults: Record<string, unknown> = {};

    for (const field of fields) {
        if (field.localizable) {
            translationDefaults[field.handle] = emptyTranslationValue(field);
        } else if (field.type === 'assets') {
            applyAssetDefaults(field, rootDefaults);
        } else {
            rootDefaults[field.handle] = emptyValueFor(field);
        }
    }

    const root: Record<string, unknown> = { ...rootDefaults };

    if (entry) {
        for (const [key, value] of Object.entries(entry)) {
            if (key === 'id' || key === 'translations' || key === 'cover_url') {
                continue;
            }

            root[key] = value ?? rootDefaults[key] ?? null;
        }
    } else if (!root.status) {
        root.status = statuses[0]?.value ?? 'draft';
    }

    if (!entry) {
        if (root.type === '') {
            root.type = 'news';
        }

        if (root.sort_order === null) {
            root.sort_order = 0;
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

function EditorialHelp({ handle }: { handle: string }): ReactNode {
    if (handle === 'post') {
        return <PostEditorHelp />;
    }

    if (handle === 'page') {
        return (
            <CpContentHelp title="Страницы и разделы сайта">
                <p>
                    CMS-страница публикуется по адресу{' '}
                    <code>/язык/pages/адрес</code>. Разделы вроде «Контакты»
                    или «Новости» — отдельные модули портала; их адреса
                    задаются системой.
                </p>
                <p>
                    Флаг «Сделать главной» подключает блоки этой страницы на
                    домашнюю (/язык/), но не меняет её URL. Наборы блоков:{' '}
                    <code>page</code> (полный), <code>homepage</code>,{' '}
                    <code>about</code>, <code>landing</code> — задаются в
                    blueprint через <code>blockset</code>.
                </p>
            </CpContentHelp>
        );
    }

    return null;
}

export default function EditorialEntryForm({
    entry,
    contentType,
    urls,
    blueprint,
    fieldOptions,
    locales,
    statuses,
    statusTransitions,
    defaultLocale,
    coverUrl,
    blockset,
    publicUrls = {},
    previewUrls = {},
    hasUnpublishedChanges = false,
    canPublish = false,
}: PageProps) {
    const isEdit = Boolean(entry?.id);
    const form = useForm(
        buildInitialFormData(blueprint, entry, locales, statuses),
    );
    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const [previewOpen, setPreviewOpen] = useState(false);
    const errors = form.errors as Record<string, string>;
    const titleField = contentType.titleField;
    const showSchedule = contentType.features.includes('schedulable');

    const autosavePayload = useMemo(() => {
        const payload: Record<string, unknown> = {};

        for (const [key, value] of Object.entries(form.data)) {
            if (AUTOSAVE_EXCLUDED_KEYS.has(key)) {
                continue;
            }

            payload[key] = value;
        }

        return payload;
    }, [form.data]);

    const autosaveState = useAutosave({
        enabled: isEdit && Boolean(urls.autosave),
        url: urls.autosave ?? '',
        data: autosavePayload,
    });

    const hasPreview =
        Boolean(entry?.id) && Object.keys(previewUrls).length > 0;

    const setTranslation = (locale: string, handle: string, value: unknown) => {
        const translations = form.data.translations as Record<
            string,
            Record<string, unknown>
        >;

        form.setData('translations', {
            ...translations,
            [locale]: {
                ...translations[locale],
                [handle]: value,
            },
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && urls.update) {
            form.put(urls.update, { preserveScroll: true });
        } else {
            form.post(urls.store, { preserveScroll: true });
        }
    };

    const title = isEdit
        ? `Редактирование — ${contentType.label}`
        : `${contentType.label} — создание`;

    const formMeta = {
        statuses,
        statusTransitions,
        showSchedule,
        coverUrl: coverUrl ?? entry?.cover_url ?? null,
    };

    const translations = form.data.translations as Record<
        string,
        Record<string, unknown>
    >;

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
                onPreview={hasPreview ? () => setPreviewOpen(true) : undefined}
                autosave={isEdit ? autosaveState : undefined}
                headerActions={
                    <>
                        {hasPreview ? (
                            <CpLivePreview
                                previewUrls={previewUrls}
                                locales={locales}
                                activeLocale={activeLocale}
                                open={previewOpen}
                                onOpenChange={setPreviewOpen}
                            />
                        ) : null}
                        {form.data.status === 'published' ? (
                            <CpViewOnSite
                                urls={publicUrls}
                                locales={locales}
                                defaultLocale={defaultLocale}
                            />
                        ) : null}
                    </>
                }
                sidebar={
                    <>
                        <CpWorkingCopyBanner
                            hasUnpublishedChanges={hasUnpublishedChanges}
                            canPublish={canPublish}
                            publishUrl={urls.publishVersion ?? null}
                        />
                        <CpBlueprintForm
                            blueprint={blueprint}
                            section="sidebar"
                            data={form.data}
                            errors={errors}
                            activeLocale={activeLocale}
                            fieldOptions={fieldOptions}
                            meta={formMeta}
                            onRootChange={(handle, value) =>
                                form.setData(handle, value as never)
                            }
                            onTranslationChange={setTranslation}
                            onAssetChange={(patch) =>
                                form.setData({ ...form.data, ...patch })
                            }
                            blockset={blockset}
                        />
                    </>
                }
            >
                <EditorialHelp handle={contentType.handle} />

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
                    onRootChange={(handle, value) =>
                        form.setData(handle, value as never)
                    }
                    onTranslationChange={setTranslation}
                    onAssetChange={(patch) =>
                        form.setData({ ...form.data, ...patch })
                    }
                    blockset={blockset}
                />
            </CpPublishForm>
        </>
    );
}

EditorialEntryForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Коллекции', href: contentHub() },
        { title: 'Запись', href: '#' },
    ],
};

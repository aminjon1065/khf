import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import { CpContentHelp } from '@/components/admin/cp/content-help';
import { CpLivePreview } from '@/components/admin/cp/live-preview';
import { CpViewOnSite } from '@/components/admin/cp/view-on-site';
import {
    CpLocaleTabs,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { CpWorkingCopyBanner } from '@/components/admin/cp/working-copy-banner';
import { autosave, index, publishVersion, store, update } from '@/routes/admin/pages';
import { useAutosave } from '@/hooks/use-autosave';
import type { BlockData } from '@/components/admin/cp/blocks-field';
import type {
    BlueprintDefinition,
    BlueprintFieldOptions,
    BlockSetDefinition,
    SelectOption,
} from '@/types/cms';

type Translation = {
    title: string;
    slug: string;
    content: string;
    blocks: BlockData[];
    seo_title: string;
    seo_description: string;
};

type LocaleOption = { code: string; native_name: string };

type PageData = {
    id: number;
    parent_id: number | null;
    status: string;
    sort_order: number;
    is_home: boolean;
    tag_ids: number[];
    cover_url: string | null;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    page: PageData | null;
    blueprint: BlueprintDefinition;
    blockset?: BlockSetDefinition;
    fieldOptions: BlueprintFieldOptions;
    locales: LocaleOption[];
    statuses: SelectOption[];
    statusTransitions: SelectOption[];
    defaultLocale: string;
    publicUrls?: Record<string, string>;
    previewUrls?: Record<string, string>;
    hasUnpublishedChanges?: boolean;
    canPublish?: boolean;
};

export default function PageForm({
    page,
    blueprint,
    blockset,
    fieldOptions,
    locales,
    statuses,
    statusTransitions,
    defaultLocale,
    publicUrls = {},
    previewUrls = {},
    hasUnpublishedChanges = false,
    canPublish = false,
}: PageProps) {
    const isEdit = Boolean(page);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = page?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            content: existing?.content ?? '',
            blocks: existing?.blocks ?? [],
            seo_title: existing?.seo_title ?? '',
            seo_description: existing?.seo_description ?? '',
        };
    });

    const form = useForm({
        status: page?.status ?? statuses[0]?.value ?? 'draft',
        parent_id: page?.parent_id ?? null,
        sort_order: page?.sort_order ?? 0,
        is_home: page?.is_home ?? false,
        tag_ids: page?.tag_ids ?? [],
        cover: null as File | null,
        cover_media_id: null as number | null,
        remove_cover: false,
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const [previewOpen, setPreviewOpen] = useState(false);
    const errors = form.errors as Record<string, string>;

    const autosavePayload = useMemo(
        () => ({
            parent_id: form.data.parent_id,
            sort_order: form.data.sort_order,
            is_home: form.data.is_home,
            tag_ids: form.data.tag_ids,
            translations: form.data.translations,
        }),
        [
            form.data.parent_id,
            form.data.sort_order,
            form.data.is_home,
            form.data.tag_ids,
            form.data.translations,
        ],
    );

    const autosaveState = useAutosave({
        enabled: isEdit && Boolean(page?.id),
        url: page?.id ? autosave(page.id).url : '',
        data: autosavePayload,
    });

    const hasPreview = Boolean(page?.id && Object.keys(previewUrls).length > 0);

    const setTranslation = (
        locale: string,
        field: string,
        value: unknown,
    ) => {
        form.setData('translations', {
            ...form.data.translations,
            [locale]: {
                ...form.data.translations[locale],
                [field]: value,
            },
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && page) {
            form.put(update(page.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const title = isEdit ? 'Редактирование страницы' : 'Новая страница';
    const formMeta = {
        statuses,
        statusTransitions,
        coverUrl: page?.cover_url ?? null,
    };

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                saveLabel={page?.id ? 'Обновить' : 'Создать'}
                modelInfo={{ type: 'page', id: page?.id ?? null }}
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
                            publishUrl={
                                page?.id
                                    ? publishVersion(page.id).url
                                    : null
                            }
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
                                form.setData(handle as keyof typeof form.data, value as never)
                            }
                            onTranslationChange={setTranslation}
                            onAssetChange={(patch) =>
                                form.setData({ ...form.data, ...patch })
                            }
                        />
                    </>
                }
            >
                <CpContentHelp title="Страницы и разделы сайта">
                    <p>
                        CMS-страница публикуется по адресу <code>/язык/pages/адрес</code>.
                        Разделы вроде «Контакты» или «Новости» — отдельные модули портала; их
                        адреса задаются системой.
                    </p>
                    <p>
                        Флаг «Сделать главной» подключает блоки этой страницы на домашнюю
                        (/язык/), но не меняет её URL. Наборы блоков: <code>page</code> (полный),{' '}
                        <code>homepage</code>, <code>about</code>, <code>landing</code> — задаются в
                        blueprint через <code>blockset</code>.
                    </p>
                </CpContentHelp>

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
                    onRootChange={(handle, value) =>
                        form.setData(handle as keyof typeof form.data, value as never)
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

PageForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Страницы', href: index() },
    ],
};

import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import { PostEditorHelp } from '@/components/admin/cp/content-help-topics';
import { CpLivePreview } from '@/components/admin/cp/live-preview';
import { CpViewOnSite } from '@/components/admin/cp/view-on-site';
import {
    CpLocaleTabs,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { CpWorkingCopyBanner } from '@/components/admin/cp/working-copy-banner';
import { autosave, index, publishVersion, store, update } from '@/routes/admin/posts';
import { useAutosave } from '@/hooks/use-autosave';
import type {
    BlueprintDefinition,
    BlueprintFieldOptions,
    SelectOption,
} from '@/types/cms';

type Translation = {
    title: string;
    slug: string;
    excerpt: string;
    body: string;
    seo_title: string;
    seo_description: string;
};

type LocaleOption = { code: string; native_name: string };

type PostData = {
    id: number;
    type: string;
    category_id: number | null;
    tag_ids: number[];
    status: string;
    published_at: string | null;
    unpublished_at: string | null;
    cover_url: string | null;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    post: PostData | null;
    blueprint: BlueprintDefinition;
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

export default function PostForm({
    post,
    blueprint,
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
    const isEdit = Boolean(post);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = post?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            excerpt: existing?.excerpt ?? '',
            body: existing?.body ?? '',
            seo_title: existing?.seo_title ?? '',
            seo_description: existing?.seo_description ?? '',
        };
    });

    const form = useForm({
        type: post?.type ?? 'news',
        category_id: post?.category_id ?? null,
        tag_ids: post?.tag_ids ?? [],
        status: post?.status ?? statuses[0]?.value ?? 'draft',
        published_at: post?.published_at ?? '',
        unpublished_at: post?.unpublished_at ?? '',
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
            type: form.data.type,
            category_id: form.data.category_id,
            tag_ids: form.data.tag_ids,
            published_at: form.data.published_at,
            unpublished_at: form.data.unpublished_at,
            translations: form.data.translations,
        }),
        [
            form.data.type,
            form.data.category_id,
            form.data.tag_ids,
            form.data.published_at,
            form.data.unpublished_at,
            form.data.translations,
        ],
    );

    const autosaveState = useAutosave({
        enabled: isEdit && Boolean(post?.id),
        url: post?.id ? autosave(post.id).url : '',
        data: autosavePayload,
    });

    const hasPreview = Boolean(post?.id && Object.keys(previewUrls).length > 0);

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

        if (isEdit && post) {
            form.put(update(post.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const title = isEdit ? 'Редактирование материала' : 'Новый материал';
    const formMeta = {
        statuses,
        statusTransitions,
        showSchedule: true,
        coverUrl: post?.cover_url ?? null,
    };

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                saveLabel={post?.id ? 'Обновить' : 'Создать'}
                modelInfo={{ type: 'post', id: post?.id ?? null }}
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
                                post?.id
                                    ? publishVersion(post.id).url
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
                <PostEditorHelp />

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
                />
            </CpPublishForm>
        </>
    );
}

PostForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Новости и материалы', href: index() },
    ],
};

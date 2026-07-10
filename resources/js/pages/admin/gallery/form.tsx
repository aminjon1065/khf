import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import {
    CpLocaleTabs,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/gallery';
import type {
    BlueprintDefinition,
    BlueprintFieldOptions,
    ExistingPhoto,
    SelectOption,
} from '@/types/cms';

type Translation = { title: string; slug: string; description: string };
type LocaleOption = { code: string; native_name: string };

type GalleryData = {
    id: number;
    status: string;
    sort_order: number;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    gallery: GalleryData | null;
    blueprint: BlueprintDefinition;
    fieldOptions: BlueprintFieldOptions;
    locales: LocaleOption[];
    statuses: SelectOption[];
    statusTransitions: SelectOption[];
    defaultLocale: string;
    existingPhotos: ExistingPhoto[];
};

export default function GalleryForm({
    gallery,
    blueprint,
    fieldOptions,
    locales,
    statuses,
    statusTransitions,
    defaultLocale,
    existingPhotos,
}: PageProps) {
    const isEdit = Boolean(gallery);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = gallery?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            description: existing?.description ?? '',
        };
    });

    const form = useForm({
        status: gallery?.status ?? statuses[0]?.value ?? 'draft',
        sort_order: gallery?.sort_order ?? 0,
        photos: [] as File[],
        remove_photos: [] as number[],
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && gallery) {
            form.put(update(gallery.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const title = isEdit ? 'Редактирование галереи' : 'Новая галерея';
    const formMeta = {
        statuses,
        statusTransitions,
        showSchedule: false,
        existingPhotos,
    };

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                saveLabel={gallery?.id ? 'Обновить' : 'Создать'}
                modelInfo={{ type: 'gallery', id: gallery?.id ?? null }}
                sidebar={
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
                    onRootChange={(handle, value) =>
                        form.setData(handle as keyof typeof form.data, value as never)
                    }
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

GalleryForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Фотогалерея', href: index() },
    ],
};

import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import {
    CpLocaleTabs,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/guides';
import type {
    BlueprintDefinition,
    BlueprintFieldOptions,
    ExistingAssetFile,
    SelectOption,
} from '@/types/cms';

type Translation = {
    title: string;
    slug: string;
    summary: string;
    content: string;
    seo_title: string;
    seo_description: string;
};

type LocaleOption = { code: string; native_name: string };

type GuideData = {
    id: number;
    hazard_type: string;
    audience: string;
    status: string;
    sort_order: number;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    guide: GuideData | null;
    blueprint: BlueprintDefinition;
    fieldOptions: BlueprintFieldOptions;
    locales: LocaleOption[];
    statuses: SelectOption[];
    statusTransitions: SelectOption[];
    defaultLocale: string;
    existingFiles: ExistingAssetFile[];
};

export default function GuideForm({
    guide,
    blueprint,
    fieldOptions,
    locales,
    statuses,
    statusTransitions,
    defaultLocale,
    existingFiles,
}: PageProps) {
    const isEdit = Boolean(guide);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = guide?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            summary: existing?.summary ?? '',
            content: existing?.content ?? '',
            seo_title: existing?.seo_title ?? '',
            seo_description: existing?.seo_description ?? '',
        };
    });

    const form = useForm({
        hazard_type: guide?.hazard_type ?? '',
        audience: guide?.audience ?? 'general',
        status: guide?.status ?? statuses[0]?.value ?? 'draft',
        sort_order: guide?.sort_order ?? 0,
        files: [] as File[],
        remove_files: [] as number[],
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && guide) {
            form.put(update(guide.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const title = isEdit ? 'Редактирование памятки' : 'Новая памятка';
    const formMeta = {
        statuses,
        statusTransitions,
        showSchedule: false,
        existingFiles,
    };

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                saveLabel={guide?.id ? 'Обновить' : 'Создать'}
                modelInfo={{ type: 'guide', id: guide?.id ?? null }}
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

GuideForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Памятки', href: index() },
    ],
};

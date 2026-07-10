import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import {
    CpLocaleTabs,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/documents';
import type {
    BlueprintDefinition,
    BlueprintFieldOptions,
    ExistingAssetFile,
    SelectOption,
} from '@/types/cms';

type Translation = { name: string; description: string };
type LocaleOption = { code: string; native_name: string };

type DocumentData = {
    id: number;
    type: string;
    source: string | null;
    document_date: string | null;
    status: string;
    sort_order: number;
    tag_ids: number[];
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    document: DocumentData | null;
    blueprint: BlueprintDefinition;
    fieldOptions: BlueprintFieldOptions;
    locales: LocaleOption[];
    statuses: SelectOption[];
    statusTransitions: SelectOption[];
    defaultLocale: string;
    existingFiles: ExistingAssetFile[];
};

export default function DocumentForm({
    document,
    blueprint,
    fieldOptions,
    locales,
    statuses,
    statusTransitions,
    defaultLocale,
    existingFiles,
}: PageProps) {
    const isEdit = Boolean(document);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = document?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            name: existing?.name ?? '',
            description: existing?.description ?? '',
        };
    });

    const form = useForm({
        type: document?.type ?? 'law',
        source: document?.source ?? '',
        document_date: document?.document_date ?? '',
        status: document?.status ?? statuses[0]?.value ?? 'draft',
        sort_order: document?.sort_order ?? 0,
        tag_ids: document?.tag_ids ?? [],
        files: [] as File[],
        remove_files: [] as number[],
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && document) {
            form.put(update(document.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const title = isEdit ? 'Редактирование документа' : 'Новый документ';
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
                saveLabel={document?.id ? 'Обновить' : 'Создать'}
                modelInfo={{ type: 'document', id: document?.id ?? null }}
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
                        Boolean(form.data.translations[code]?.name)
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
                    titleFieldHandle="name"
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

DocumentForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Документы', href: index() },
    ],
};

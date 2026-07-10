import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import {
    CpLocaleTabs,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/structure';
import type {
    BlueprintDefinition,
    BlueprintFieldOptions,
    SelectOption,
} from '@/types/cms';

type Translation = { name: string };
type LocaleOption = { code: string; native_name: string };

type SubdivisionData = {
    id: number;
    status: string;
    parent_id: number | null;
    sort_order: number;
    email: string | null;
    phone: string | null;
    staff_count: number | null;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    subdivision: SubdivisionData | null;
    blueprint: BlueprintDefinition;
    fieldOptions: BlueprintFieldOptions;
    locales: LocaleOption[];
    statuses: SelectOption[];
    statusTransitions: SelectOption[];
    defaultLocale: string;
};

export default function SubdivisionForm({
    subdivision,
    blueprint,
    fieldOptions,
    locales,
    statuses,
    statusTransitions,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(subdivision);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = subdivision?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            name: existing?.name ?? '',
        };
    });

    const form = useForm({
        status: subdivision?.status ?? statuses[0]?.value ?? 'draft',
        parent_id: subdivision?.parent_id ?? null,
        sort_order: subdivision?.sort_order ?? 0,
        email: subdivision?.email ?? '',
        phone: subdivision?.phone ?? '',
        staff_count: subdivision?.staff_count ?? '',
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && subdivision) {
            form.put(update(subdivision.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const title = isEdit
        ? 'Редактирование подразделения'
        : 'Новое подразделение';
    const formMeta = {
        statuses,
        statusTransitions,
        showSchedule: false,
    };

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                saveLabel={subdivision?.id ? 'Обновить' : 'Создать'}
                modelInfo={{ type: 'subdivision', id: subdivision?.id ?? null }}
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

SubdivisionForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Структура', href: index() },
    ],
};

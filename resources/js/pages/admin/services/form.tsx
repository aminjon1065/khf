import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import {
    CpLocaleTabs,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/services';
import type {
    BlueprintDefinition,
    BlueprintFieldOptions,
    SelectOption,
} from '@/types/cms';

type Translation = { title: string };
type LocaleOption = { code: string; native_name: string };

type ServiceData = {
    id: number;
    category: string;
    status: string;
    is_online: boolean;
    external_url: string | null;
    processing_time: string | null;
    fee: string | null;
    sort_order: number;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    service: ServiceData | null;
    blueprint: BlueprintDefinition;
    fieldOptions: BlueprintFieldOptions;
    locales: LocaleOption[];
    statuses: SelectOption[];
    statusTransitions: SelectOption[];
    defaultLocale: string;
};

export default function ServiceForm({
    service,
    blueprint,
    fieldOptions,
    locales,
    statuses,
    statusTransitions,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(service);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = service?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
        };
    });

    const form = useForm({
        status: service?.status ?? statuses[0]?.value ?? 'draft',
        sort_order: service?.sort_order ?? 0,
        category: service?.category ?? '',
        is_online: service?.is_online ?? false,
        external_url: service?.external_url ?? '',
        processing_time: service?.processing_time ?? '',
        fee: service?.fee ?? '',
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && service) {
            form.put(update(service.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const title = isEdit ? 'Редактирование услуги' : 'Новая услуга';
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
                saveLabel={service?.id ? 'Обновить' : 'Создать'}
                modelInfo={{ type: 'service', id: service?.id ?? null }}
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
                    titleFieldHandle="title"
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

ServiceForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Услуги', href: index() },
    ],
};

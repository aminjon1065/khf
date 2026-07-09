import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpRichTextField, CpTextField } from '@/components/admin/cp/fields';
import {
    CpLocaleTabs,
    CpPanel,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/services';

type Translation = {
    title: string;
    slug: string;
    summary: string;
    description: string;
    eligibility: string;
    required_documents: string;
    seo_title: string;
    seo_description: string;
};

type Option = { value: string; label: string };
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
    categories: Option[];
    statuses: Option[];
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function ServiceForm({
    service,
    categories,
    statuses,
    locales,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(service);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = service?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            summary: existing?.summary ?? '',
            description: existing?.description ?? '',
            eligibility: existing?.eligibility ?? '',
            required_documents: existing?.required_documents ?? '',
            seo_title: existing?.seo_title ?? '',
            seo_description: existing?.seo_description ?? '',
        };
    });

    const form = useForm({
        category: service?.category ?? categories[0]?.value ?? '',
        status: service?.status ?? 'draft',
        is_online: service?.is_online ?? false,
        external_url: service?.external_url ?? '',
        processing_time: service?.processing_time ?? '',
        fee: service?.fee ?? '',
        sort_order: service?.sort_order ?? 0,
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

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && service) {
            form.put(update(service.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование услуги' : 'Новая услуга';

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                modelInfo={{ type: 'service', id: service?.id ?? null }}
                sidebar={
                    <CpPanel title="Параметры">
                        <div className="space-y-2">
                            <Label htmlFor="category">Категория</Label>
                            <Select
                                value={form.data.category}
                                onValueChange={(value) =>
                                    form.setData('category', value)
                                }
                            >
                                <SelectTrigger id="category">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem
                                            key={category.value}
                                            value={category.value}
                                        >
                                            {category.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.category} />
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
                        </div>
                        <CpTextField
                            label="Срок оказания"
                            value={form.data.processing_time}
                            onChange={(value) =>
                                form.setData('processing_time', value)
                            }
                            error={errors.processing_time}
                        />
                        <CpTextField
                            label="Стоимость"
                            value={form.data.fee}
                            onChange={(value) => form.setData('fee', value)}
                            error={errors.fee}
                        />
                        <CpTextField
                            label="Ссылка (онлайн-подача)"
                            value={form.data.external_url}
                            onChange={(value) =>
                                form.setData('external_url', value)
                            }
                            error={errors.external_url}
                        />
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="is_online"
                                checked={form.data.is_online}
                                onCheckedChange={(checked) =>
                                    form.setData('is_online', checked === true)
                                }
                            />
                            <Label htmlFor="is_online">Доступна онлайн</Label>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="sort_order">Порядок</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                min={0}
                                value={form.data.sort_order}
                                onChange={(event) =>
                                    form.setData(
                                        'sort_order',
                                        Number(event.target.value),
                                    )
                                }
                            />
                        </div>
                    </CpPanel>
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
                        aria-label="Название услуги"
                        value={active.title}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'title',
                                event.target.value,
                            )
                        }
                        placeholder="Название услуги"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={
                            errors[`translations.${activeLocale}.title`]
                        }
                    />
                </div>

                <CpPanel title="Краткое описание">
                    <CpTextField
                        label="Аннотация"
                        value={active.summary}
                        onChange={(value) =>
                            setTranslation(activeLocale, 'summary', value)
                        }
                        error={errors[`translations.${activeLocale}.summary`]}
                    />
                </CpPanel>

                <CpPanel title="Описание">
                    <CpRichTextField
                        label="Подробное описание"
                        editorKey={`${activeLocale}-description`}
                        value={active.description}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'description', html)
                        }
                        error={
                            errors[`translations.${activeLocale}.description`]
                        }
                    />
                </CpPanel>

                <CpPanel title="Условия">
                    <CpRichTextField
                        label="Кто может обратиться"
                        editorKey={`${activeLocale}-eligibility`}
                        value={active.eligibility}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'eligibility', html)
                        }
                        error={
                            errors[`translations.${activeLocale}.eligibility`]
                        }
                    />
                    <CpRichTextField
                        label="Необходимые документы"
                        editorKey={`${activeLocale}-documents`}
                        value={active.required_documents}
                        onChange={(html) =>
                            setTranslation(
                                activeLocale,
                                'required_documents',
                                html,
                            )
                        }
                        error={
                            errors[
                                `translations.${activeLocale}.required_documents`
                            ]
                        }
                    />
                </CpPanel>
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

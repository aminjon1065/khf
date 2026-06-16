import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpRichTextField } from '@/components/admin/cp/fields';
import {
    CpLocaleTabs,
    CpPanel,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/tenders';

type Translation = {
    title: string;
    slug: string;
    organizer: string;
    summary: string;
    description: string;
    requirements: string;
    terms: string;
    seo_title: string;
    seo_description: string;
};

type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };

type TenderData = {
    id: number;
    tender_number: string | null;
    type: string;
    status: string;
    budget: string | null;
    lots_count: number;
    published_at: string | null;
    deadline_at: string | null;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    tender: TenderData | null;
    locales: LocaleOption[];
    tenderTypes: Option[];
    statuses: Option[];
    defaultLocale: string;
};

const emptyTranslation: Translation = {
    title: '',
    slug: '',
    organizer: '',
    summary: '',
    description: '',
    requirements: '',
    terms: '',
    seo_title: '',
    seo_description: '',
};

export default function TenderForm({
    tender,
    locales,
    tenderTypes,
    statuses,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(tender);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = tender?.translations?.[locale.code];
        initialTranslations[locale.code] = { ...emptyTranslation, ...existing };
    });

    const form = useForm({
        tender_number: tender?.tender_number ?? '',
        type: tender?.type ?? tenderTypes[0]?.value ?? 'goods',
        status: tender?.status ?? statuses[0]?.value ?? 'draft',
        budget: tender?.budget ?? '',
        lots_count: tender?.lots_count ?? 1,
        published_at: tender?.published_at ?? '',
        deadline_at: tender?.deadline_at ?? '',
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

        if (isEdit && tender) {
            form.put(update(tender.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование тендера' : 'Новый тендер';

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                sidebar={
                    <CpPanel title="Публикация">
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
                            <InputError message={errors.status} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="type">Тип закупки</Label>
                            <Select
                                value={form.data.type}
                                onValueChange={(value) =>
                                    form.setData('type', value)
                                }
                            >
                                <SelectTrigger id="type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {tenderTypes.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.type} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="tender_number">Номер тендера</Label>
                            <Input
                                id="tender_number"
                                value={form.data.tender_number}
                                onChange={(event) =>
                                    form.setData(
                                        'tender_number',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.tender_number} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="budget">
                                Ориентировочная стоимость
                            </Label>
                            <Input
                                id="budget"
                                type="number"
                                min={0}
                                step="0.01"
                                value={form.data.budget}
                                onChange={(event) =>
                                    form.setData('budget', event.target.value)
                                }
                            />
                            <InputError message={errors.budget} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="lots_count">Количество лотов</Label>
                            <Input
                                id="lots_count"
                                type="number"
                                min={1}
                                value={form.data.lots_count}
                                onChange={(event) =>
                                    form.setData(
                                        'lots_count',
                                        Number(event.target.value),
                                    )
                                }
                            />
                            <InputError message={errors.lots_count} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="published_at">
                                Дата публикации
                            </Label>
                            <Input
                                id="published_at"
                                type="datetime-local"
                                value={form.data.published_at}
                                onChange={(event) =>
                                    form.setData(
                                        'published_at',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.published_at} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="deadline_at">Срок подачи</Label>
                            <Input
                                id="deadline_at"
                                type="date"
                                value={form.data.deadline_at}
                                onChange={(event) =>
                                    form.setData(
                                        'deadline_at',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.deadline_at} />
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
                        aria-label="Название тендера"
                        value={active.title}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'title',
                                event.target.value,
                            )
                        }
                        placeholder="Название тендера"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={errors[`translations.${activeLocale}.title`]}
                    />
                </div>

                <CpPanel title="Основное">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="slug">ЧПУ (slug)</Label>
                            <Input
                                id="slug"
                                value={active.slug}
                                onChange={(event) =>
                                    setTranslation(
                                        activeLocale,
                                        'slug',
                                        event.target.value,
                                    )
                                }
                                placeholder="оставьте пустым для авто"
                            />
                            <InputError
                                message={
                                    errors[`translations.${activeLocale}.slug`]
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="organizer">Организатор</Label>
                            <Input
                                id="organizer"
                                value={active.organizer}
                                onChange={(event) =>
                                    setTranslation(
                                        activeLocale,
                                        'organizer',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="summary">Краткое описание</Label>
                        <Textarea
                            id="summary"
                            rows={2}
                            value={active.summary}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'summary',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                errors[`translations.${activeLocale}.summary`]
                            }
                        />
                    </div>
                </CpPanel>

                <CpPanel title="Описание">
                    <CpRichTextField
                        label="Описание тендера"
                        editorKey={`${activeLocale}-description`}
                        value={active.description}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'description', html)
                        }
                        error={
                            errors[`translations.${activeLocale}.description`]
                        }
                    />
                    <CpRichTextField
                        label="Условия участия"
                        editorKey={`${activeLocale}-requirements`}
                        value={active.requirements}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'requirements', html)
                        }
                        error={
                            errors[`translations.${activeLocale}.requirements`]
                        }
                    />
                    <CpRichTextField
                        label="Условия контракта"
                        editorKey={`${activeLocale}-terms`}
                        value={active.terms}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'terms', html)
                        }
                        error={errors[`translations.${activeLocale}.terms`]}
                    />
                </CpPanel>

                <CpPanel title="SEO">
                    <div className="space-y-2">
                        <Label htmlFor="seo_title">SEO заголовок</Label>
                        <Input
                            id="seo_title"
                            value={active.seo_title}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'seo_title',
                                    event.target.value,
                                )
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="seo_description">SEO описание</Label>
                        <Input
                            id="seo_description"
                            value={active.seo_description}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'seo_description',
                                    event.target.value,
                                )
                            }
                        />
                    </div>
                </CpPanel>
            </CpPublishForm>
        </>
    );
}

TenderForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Тендеры', href: index() },
    ],
};

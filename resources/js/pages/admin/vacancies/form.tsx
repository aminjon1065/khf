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
import { index, store, update } from '@/routes/admin/vacancies';

type Translation = {
    title: string;
    slug: string;
    department: string;
    location: string;
    salary: string;
    summary: string;
    description: string;
    requirements: string;
    responsibilities: string;
    seo_title: string;
    seo_description: string;
};

type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };

type VacancyData = {
    id: number;
    employment_type: string;
    status: string;
    positions_count: number;
    published_at: string | null;
    deadline_at: string | null;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    vacancy: VacancyData | null;
    locales: LocaleOption[];
    employmentTypes: Option[];
    statuses: Option[];
    defaultLocale: string;
};

const emptyTranslation: Translation = {
    title: '',
    slug: '',
    department: '',
    location: '',
    salary: '',
    summary: '',
    description: '',
    requirements: '',
    responsibilities: '',
    seo_title: '',
    seo_description: '',
};

export default function VacancyForm({
    vacancy,
    locales,
    employmentTypes,
    statuses,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(vacancy);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = vacancy?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            ...emptyTranslation,
            ...existing,
        };
    });

    const form = useForm({
        employment_type:
            vacancy?.employment_type ??
            employmentTypes[0]?.value ??
            'full_time',
        status: vacancy?.status ?? statuses[0]?.value ?? 'draft',
        positions_count: vacancy?.positions_count ?? 1,
        published_at: vacancy?.published_at ?? '',
        deadline_at: vacancy?.deadline_at ?? '',
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

        if (isEdit && vacancy) {
            form.put(update(vacancy.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование вакансии' : 'Новая вакансия';

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
                            <Label htmlFor="employment_type">
                                Тип занятости
                            </Label>
                            <Select
                                value={form.data.employment_type}
                                onValueChange={(value) =>
                                    form.setData('employment_type', value)
                                }
                            >
                                <SelectTrigger id="employment_type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {employmentTypes.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.employment_type} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="positions_count">
                                Количество мест
                            </Label>
                            <Input
                                id="positions_count"
                                type="number"
                                min={1}
                                value={form.data.positions_count}
                                onChange={(event) =>
                                    form.setData(
                                        'positions_count',
                                        Number(event.target.value),
                                    )
                                }
                            />
                            <InputError message={errors.positions_count} />
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
                        aria-label="Название должности"
                        value={active.title}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'title',
                                event.target.value,
                            )
                        }
                        placeholder="Название должности"
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
                            <Label htmlFor="department">Подразделение</Label>
                            <Input
                                id="department"
                                value={active.department}
                                onChange={(event) =>
                                    setTranslation(
                                        activeLocale,
                                        'department',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="location">Место работы</Label>
                            <Input
                                id="location"
                                value={active.location}
                                onChange={(event) =>
                                    setTranslation(
                                        activeLocale,
                                        'location',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="salary">Оплата труда</Label>
                            <Input
                                id="salary"
                                value={active.salary}
                                onChange={(event) =>
                                    setTranslation(
                                        activeLocale,
                                        'salary',
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
                        label="Описание вакансии"
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
                        label="Квалификационные требования"
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
                        label="Должностные обязанности"
                        editorKey={`${activeLocale}-responsibilities`}
                        value={active.responsibilities}
                        onChange={(html) =>
                            setTranslation(
                                activeLocale,
                                'responsibilities',
                                html,
                            )
                        }
                        error={
                            errors[
                                `translations.${activeLocale}.responsibilities`
                            ]
                        }
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

VacancyForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Вакансии', href: index() },
    ],
};

import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpRichTextField } from '@/components/admin/cp/fields';
import {
    CpLocaleTabs,
    CpPanel,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { CpRelationField } from '@/components/admin/cp/relation-field';
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
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/structure';

type Translation = {
    name: string;
    head: string;
    functions: string;
    address: string;
};

type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };
type ParentOption = { id: number; name: string };

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
    parents: ParentOption[];
    locales: LocaleOption[];
    statuses: Option[];
    defaultLocale: string;
};

const emptyTranslation: Translation = {
    name: '',
    head: '',
    functions: '',
    address: '',
};

export default function SubdivisionForm({
    subdivision,
    parents,
    locales,
    statuses,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(subdivision);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = subdivision?.translations?.[locale.code];
        initialTranslations[locale.code] = { ...emptyTranslation, ...existing };
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

        if (isEdit && subdivision) {
            form.put(update(subdivision.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit
        ? 'Редактирование подразделения'
        : 'Новое подразделение';

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                sidebar={
                    <>
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
                            <CpRelationField
                                id="parent_id"
                                label="Вышестоящее подразделение"
                                value={form.data.parent_id}
                                options={parents}
                                onChange={(value) =>
                                    form.setData('parent_id', value)
                                }
                                placeholder="— Корневое —"
                                error={errors.parent_id}
                            />
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
                                <InputError message={errors.sort_order} />
                            </div>
                        </CpPanel>

                        <CpPanel title="Контакты">
                            <div className="space-y-2">
                                <Label htmlFor="staff_count">
                                    Численность работников
                                </Label>
                                <Input
                                    id="staff_count"
                                    type="number"
                                    min={0}
                                    value={form.data.staff_count}
                                    onChange={(event) =>
                                        form.setData(
                                            'staff_count',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.staff_count} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="email">E-mail</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="phone">Телефон</Label>
                                <Input
                                    id="phone"
                                    value={form.data.phone}
                                    onChange={(event) =>
                                        form.setData(
                                            'phone',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.phone} />
                            </div>
                        </CpPanel>
                    </>
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

                <div>
                    <input
                        aria-label="Название"
                        value={active.name}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'name',
                                event.target.value,
                            )
                        }
                        placeholder="Название подразделения"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={errors[`translations.${activeLocale}.name`]}
                    />
                </div>

                <CpPanel title="Основное">
                    <div className="space-y-2">
                        <Label htmlFor="head">Руководитель</Label>
                        <Input
                            id="head"
                            value={active.head}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'head',
                                    event.target.value,
                                )
                            }
                            placeholder="ФИО и должность руководителя"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="address">Адрес</Label>
                        <Input
                            id="address"
                            value={active.address}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'address',
                                    event.target.value,
                                )
                            }
                        />
                    </div>
                    <CpRichTextField
                        label="Задачи и функции"
                        editorKey={`${activeLocale}-functions`}
                        value={active.functions}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'functions', html)
                        }
                        error={errors[`translations.${activeLocale}.functions`]}
                    />
                </CpPanel>
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

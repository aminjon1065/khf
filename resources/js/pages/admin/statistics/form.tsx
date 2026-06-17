import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
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
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/statistics';

type Translation = { label: string; unit: string };
type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };

type StatisticData = {
    id: number;
    status: string;
    value: string;
    year: number | null;
    sort_order: number;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    statistic: StatisticData | null;
    statuses: Option[];
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function StatisticForm({
    statistic,
    statuses,
    locales,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(statistic);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = statistic?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            label: existing?.label ?? '',
            unit: existing?.unit ?? '',
        };
    });

    const form = useForm({
        status: statistic?.status ?? statuses[0]?.value ?? 'draft',
        value: statistic?.value ?? '',
        year: statistic?.year ?? '',
        sort_order: statistic?.sort_order ?? 0,
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

        if (isEdit && statistic) {
            form.put(update(statistic.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование показателя' : 'Новый показатель';

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
                            <Label htmlFor="value">Значение</Label>
                            <Input
                                id="value"
                                value={form.data.value}
                                onChange={(event) =>
                                    form.setData('value', event.target.value)
                                }
                                placeholder="например: 1 234 или 98%"
                            />
                            <InputError message={errors.value} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="year">Год</Label>
                            <Input
                                id="year"
                                type="number"
                                min={1900}
                                max={2200}
                                value={form.data.year}
                                onChange={(event) =>
                                    form.setData('year', event.target.value)
                                }
                            />
                            <InputError message={errors.year} />
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
                            <InputError message={errors.sort_order} />
                        </div>
                    </CpPanel>
                }
            >
                <CpLocaleTabs
                    locales={locales}
                    active={activeLocale}
                    onChange={setActiveLocale}
                    isComplete={(code) =>
                        Boolean(form.data.translations[code]?.label)
                    }
                />

                <div>
                    <input
                        aria-label="Название показателя"
                        value={active.label}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'label',
                                event.target.value,
                            )
                        }
                        placeholder="Например: Спасательных операций"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={errors[`translations.${activeLocale}.label`]}
                    />
                </div>

                <CpPanel title="Единица измерения">
                    <div className="space-y-2">
                        <Label htmlFor="unit">Единица (необязательно)</Label>
                        <Input
                            id="unit"
                            value={active.unit}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'unit',
                                    event.target.value,
                                )
                            }
                            placeholder="например: человек, %, ед."
                        />
                        <InputError
                            message={
                                errors[`translations.${activeLocale}.unit`]
                            }
                        />
                    </div>
                </CpPanel>
            </CpPublishForm>
        </>
    );
}

StatisticForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Статистика', href: index() },
    ],
};

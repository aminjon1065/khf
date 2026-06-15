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
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/categories';

type Translation = { name: string; slug: string };
type LocaleOption = { code: string; native_name: string };

type CategoryData = {
    id: number;
    sort_order: number;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    category: CategoryData | null;
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function CategoryForm({
    category,
    locales,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(category);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = category?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            name: existing?.name ?? '',
            slug: existing?.slug ?? '',
        };
    });

    const form = useForm({
        sort_order: category?.sort_order ?? 0,
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

        if (isEdit && category) {
            form.put(update(category.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование рубрики' : 'Новая рубрика';

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
                        placeholder="Название рубрики"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={errors[`translations.${activeLocale}.name`]}
                    />
                </div>

                <CpPanel title="Параметры">
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
                        />
                        <InputError
                            message={
                                errors[`translations.${activeLocale}.slug`]
                            }
                        />
                    </div>
                </CpPanel>
            </CpPublishForm>
        </>
    );
}

CategoryForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Рубрики', href: index() },
    ],
};

import { Head, Link, useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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

    return (
        <>
            <Head title={isEdit ? 'Редактирование рубрики' : 'Новая рубрика'} />

            <form
                onSubmit={submit}
                className="flex h-full flex-1 flex-col gap-6 p-4"
            >
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {isEdit ? 'Редактирование рубрики' : 'Новая рубрика'}
                    </h1>
                    <div className="flex gap-2">
                        <Button type="button" variant="outline" asChild>
                            <Link href={index().url}>Отмена</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Сохранить
                        </Button>
                    </div>
                </div>

                <div className="max-w-xs space-y-2">
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

                <div className="flex flex-wrap gap-2 border-b pb-2">
                    {locales.map((locale) => (
                        <Button
                            key={locale.code}
                            type="button"
                            variant={
                                activeLocale === locale.code
                                    ? 'default'
                                    : 'ghost'
                            }
                            size="sm"
                            className="gap-2"
                            onClick={() => setActiveLocale(locale.code)}
                        >
                            {locale.native_name}
                            {Boolean(
                                form.data.translations[locale.code]?.name,
                            ) && <Check className="size-3.5 text-green-600" />}
                        </Button>
                    ))}
                </div>

                <div className="grid max-w-2xl gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="name">Название</Label>
                        <Input
                            id="name"
                            value={active.name}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'name',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                errors[`translations.${activeLocale}.name`]
                            }
                        />
                    </div>
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
                </div>
            </form>
        </>
    );
}

CategoryForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Рубрики', href: index() },
    ],
};

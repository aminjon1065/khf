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
import { index, store, update } from '@/routes/admin/tags';

type Translation = { name: string; slug: string };
type LocaleOption = { code: string; native_name: string };

type TagData = {
    id: number;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    tag: TagData | null;
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function TagForm({ tag, locales, defaultLocale }: PageProps) {
    const isEdit = Boolean(tag);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = tag?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            name: existing?.name ?? '',
            slug: existing?.slug ?? '',
        };
    });

    const form = useForm({
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

        if (isEdit && tag) {
            form.put(update(tag.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование тега' : 'Новый тег';

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
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
                        placeholder="Название тега"
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

TagForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Теги', href: index() },
    ],
};

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
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/pages';

type Translation = {
    title: string;
    slug: string;
    content: string;
    seo_title: string;
    seo_description: string;
};

type LocaleOption = { code: string; native_name: string };
type StatusOption = { value: string; label: string };
type ParentOption = { id: number; title: string };

type PageData = {
    id: number;
    parent_id: number | null;
    status: string;
    sort_order: number;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    page: PageData | null;
    locales: LocaleOption[];
    statuses: StatusOption[];
    parents: ParentOption[];
    defaultLocale: string;
};

export default function PageForm({
    page,
    locales,
    statuses,
    parents,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(page);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = page?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            content: existing?.content ?? '',
            seo_title: existing?.seo_title ?? '',
            seo_description: existing?.seo_description ?? '',
        };
    });

    const form = useForm({
        status: page?.status ?? statuses[0]?.value ?? 'draft',
        parent_id: page?.parent_id ?? null,
        sort_order: page?.sort_order ?? 0,
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

        if (isEdit && page) {
            form.put(update(page.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование страницы' : 'Новая страница';

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
                            <Label htmlFor="parent">
                                Родительская страница
                            </Label>
                            <Select
                                value={
                                    form.data.parent_id
                                        ? String(form.data.parent_id)
                                        : 'none'
                                }
                                onValueChange={(value) =>
                                    form.setData(
                                        'parent_id',
                                        value === 'none' ? null : Number(value),
                                    )
                                }
                            >
                                <SelectTrigger id="parent">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        — Нет —
                                    </SelectItem>
                                    {parents.map((parent) => (
                                        <SelectItem
                                            key={parent.id}
                                            value={String(parent.id)}
                                        >
                                            {parent.title}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.parent_id} />
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
                        Boolean(form.data.translations[code]?.title)
                    }
                />

                <div>
                    <input
                        aria-label="Заголовок"
                        value={active.title}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'title',
                                event.target.value,
                            )
                        }
                        placeholder="Заголовок"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={errors[`translations.${activeLocale}.title`]}
                    />
                </div>

                <CpPanel title="Содержание">
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
                    <CpRichTextField
                        label="Содержимое"
                        editorKey={activeLocale}
                        value={active.content}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'content', html)
                        }
                        error={errors[`translations.${activeLocale}.content`]}
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

PageForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Страницы', href: index() },
    ],
};

import { Head, Link, useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState  } from 'react';
import type {FormEvent} from 'react';
import InputError from '@/components/input-error';
import { RichTextEditor } from '@/components/rich-text-editor';
import { Button } from '@/components/ui/button';
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

export default function PageForm({ page, locales, statuses, parents, defaultLocale }: PageProps) {
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

    const setTranslation = (locale: string, field: keyof Translation, value: string) => {
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

    return (
        <>
            <Head title={isEdit ? 'Редактирование страницы' : 'Новая страница'} />

            <form onSubmit={submit} className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {isEdit ? 'Редактирование страницы' : 'Новая страница'}
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

                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="status">Статус</Label>
                        <Select value={form.data.status} onValueChange={(value) => form.setData('status', value)}>
                            <SelectTrigger id="status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {statuses.map((status) => (
                                    <SelectItem key={status.value} value={status.value}>
                                        {status.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.status} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="parent">Родительская страница</Label>
                        <Select
                            value={form.data.parent_id ? String(form.data.parent_id) : 'none'}
                            onValueChange={(value) => form.setData('parent_id', value === 'none' ? null : Number(value))}
                        >
                            <SelectTrigger id="parent">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">— Нет —</SelectItem>
                                {parents.map((parent) => (
                                    <SelectItem key={parent.id} value={String(parent.id)}>
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
                            onChange={(event) => form.setData('sort_order', Number(event.target.value))}
                        />
                        <InputError message={errors.sort_order} />
                    </div>
                </div>

                <div className="flex flex-wrap gap-2 border-b pb-2">
                    {locales.map((locale) => (
                        <Button
                            key={locale.code}
                            type="button"
                            variant={activeLocale === locale.code ? 'default' : 'ghost'}
                            size="sm"
                            className="gap-2"
                            onClick={() => setActiveLocale(locale.code)}
                        >
                            {locale.native_name}
                            {Boolean(form.data.translations[locale.code]?.title) && (
                                <Check className="size-3.5 text-green-600" />
                            )}
                        </Button>
                    ))}
                </div>

                <div className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="title">Заголовок</Label>
                            <Input
                                id="title"
                                value={active.title}
                                onChange={(event) => setTranslation(activeLocale, 'title', event.target.value)}
                            />
                            <InputError message={errors[`translations.${activeLocale}.title`]} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="slug">ЧПУ (slug)</Label>
                            <Input
                                id="slug"
                                value={active.slug}
                                onChange={(event) => setTranslation(activeLocale, 'slug', event.target.value)}
                            />
                            <InputError message={errors[`translations.${activeLocale}.slug`]} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="content">Содержимое</Label>
                        <RichTextEditor
                            key={activeLocale}
                            value={active.content}
                            onChange={(html) => setTranslation(activeLocale, 'content', html)}
                        />
                        <InputError message={errors[`translations.${activeLocale}.content`]} />
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="seo_title">SEO заголовок</Label>
                            <Input
                                id="seo_title"
                                value={active.seo_title}
                                onChange={(event) => setTranslation(activeLocale, 'seo_title', event.target.value)}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="seo_description">SEO описание</Label>
                            <Input
                                id="seo_description"
                                value={active.seo_description}
                                onChange={(event) => setTranslation(activeLocale, 'seo_description', event.target.value)}
                            />
                        </div>
                    </div>
                </div>
            </form>
        </>
    );
}

PageForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Страницы', href: index() },
    ],
};

import { Head, Link, useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState  } from 'react';
import type {FormEvent} from 'react';
import InputError from '@/components/input-error';
import { RichTextEditor } from '@/components/rich-text-editor';
import { Button } from '@/components/ui/button';
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
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/posts';

type Translation = {
    title: string;
    slug: string;
    excerpt: string;
    body: string;
    seo_title: string;
    seo_description: string;
};

type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };
type CategoryOption = { id: number; name: string };

type PostData = {
    id: number;
    type: string;
    category_id: number | null;
    status: string;
    published_at: string | null;
    cover_url: string | null;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    post: PostData | null;
    locales: LocaleOption[];
    types: Option[];
    statuses: Option[];
    categories: CategoryOption[];
    defaultLocale: string;
};

export default function PostForm({ post, locales, types, statuses, categories, defaultLocale }: PageProps) {
    const isEdit = Boolean(post);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = post?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            excerpt: existing?.excerpt ?? '',
            body: existing?.body ?? '',
            seo_title: existing?.seo_title ?? '',
            seo_description: existing?.seo_description ?? '',
        };
    });

    const form = useForm({
        type: post?.type ?? types[0]?.value ?? 'news',
        category_id: post?.category_id ?? null,
        status: post?.status ?? statuses[0]?.value ?? 'draft',
        published_at: post?.published_at ?? '',
        cover: null as File | null,
        remove_cover: false,
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

        if (isEdit && post) {
            form.put(update(post.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];

    return (
        <>
            <Head title={isEdit ? 'Редактирование материала' : 'Новый материал'} />

            <form onSubmit={submit} className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {isEdit ? 'Редактирование материала' : 'Новый материал'}
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

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="type">Тип</Label>
                        <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
                            <SelectTrigger id="type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {types.map((type) => (
                                    <SelectItem key={type.value} value={type.value}>
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.type} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="category">Рубрика</Label>
                        <Select
                            value={form.data.category_id ? String(form.data.category_id) : 'none'}
                            onValueChange={(value) => form.setData('category_id', value === 'none' ? null : Number(value))}
                        >
                            <SelectTrigger id="category">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">— Нет —</SelectItem>
                                {categories.map((category) => (
                                    <SelectItem key={category.id} value={String(category.id)}>
                                        {category.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.category_id} />
                    </div>
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
                        <Label htmlFor="published_at">Дата публикации</Label>
                        <Input
                            id="published_at"
                            type="datetime-local"
                            value={form.data.published_at}
                            onChange={(event) => form.setData('published_at', event.target.value)}
                        />
                        <InputError message={errors.published_at} />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="cover">Обложка</Label>
                    {post?.cover_url && !form.data.remove_cover && (
                        <img
                            src={post.cover_url}
                            alt=""
                            className="h-32 w-auto rounded-md border object-cover"
                        />
                    )}
                    <Input
                        id="cover"
                        type="file"
                        accept="image/*"
                        className="max-w-sm"
                        onChange={(event) => form.setData('cover', event.target.files?.[0] ?? null)}
                    />
                    <InputError message={errors.cover} />
                    {post?.cover_url && (
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={form.data.remove_cover}
                                onCheckedChange={(checked) => form.setData('remove_cover', checked === true)}
                            />
                            Удалить текущую обложку
                        </label>
                    )}
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
                        <Label htmlFor="excerpt">Анонс</Label>
                        <Textarea
                            id="excerpt"
                            rows={2}
                            value={active.excerpt}
                            onChange={(event) => setTranslation(activeLocale, 'excerpt', event.target.value)}
                        />
                        <InputError message={errors[`translations.${activeLocale}.excerpt`]} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="body">Полный текст</Label>
                        <RichTextEditor
                            key={activeLocale}
                            value={active.body}
                            onChange={(html) => setTranslation(activeLocale, 'body', html)}
                        />
                        <InputError message={errors[`translations.${activeLocale}.body`]} />
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

PostForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Новости и материалы', href: index() },
    ],
};

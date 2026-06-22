import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpAssetsField } from '@/components/admin/cp/assets-field';
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

export default function PostForm({
    post,
    locales,
    types,
    statuses,
    categories,
    defaultLocale,
}: PageProps) {
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
        cover_media_id: null as number | null,
        remove_cover: false,
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

        if (isEdit && post) {
            form.put(update(post.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование материала' : 'Новый материал';

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                saveLabel={post?.id ? 'Обновить' : 'Создать'}
                modelInfo={{ type: 'post', id: post?.id ?? null }}
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
                            <div className="space-y-2">
                                <Label htmlFor="type">Тип</Label>
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
                                        {types.map((type) => (
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
                            <CpRelationField
                                id="category"
                                label="Рубрика"
                                value={form.data.category_id}
                                options={categories}
                                onChange={(value) =>
                                    form.setData('category_id', value)
                                }
                                placeholder="— Нет —"
                                error={errors.category_id}
                            />
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
                        </CpPanel>

                        <CpPanel title="Обложка">
                            <CpAssetsField
                                label="Изображение обложки"
                                instructions="Загрузите файл или выберите из медиабиблиотеки"
                                currentUrl={post?.cover_url ?? null}
                                file={form.data.cover}
                                mediaId={form.data.cover_media_id}
                                removed={form.data.remove_cover}
                                onUpload={(file) =>
                                    form.setData({
                                        ...form.data,
                                        cover: file,
                                        cover_media_id: null,
                                        remove_cover: false,
                                    })
                                }
                                onPickAsset={(asset) =>
                                    form.setData({
                                        ...form.data,
                                        cover: null,
                                        cover_media_id: asset?.id ?? null,
                                        remove_cover: false,
                                    })
                                }
                                onClear={() =>
                                    form.setData({
                                        ...form.data,
                                        cover: null,
                                        cover_media_id: null,
                                        remove_cover: true,
                                    })
                                }
                                error={errors.cover ?? errors.cover_media_id}
                            />
                        </CpPanel>
                    </>
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
                            placeholder="оставьте пустым для авто"
                        />
                        <InputError
                            message={
                                errors[`translations.${activeLocale}.slug`]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="excerpt">Анонс</Label>
                        <Textarea
                            id="excerpt"
                            rows={2}
                            value={active.excerpt}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'excerpt',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                errors[`translations.${activeLocale}.excerpt`]
                            }
                        />
                    </div>
                    <CpRichTextField
                        label="Полный текст"
                        editorKey={activeLocale}
                        value={active.body}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'body', html)
                        }
                        error={errors[`translations.${activeLocale}.body`]}
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

PostForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Новости и материалы', href: index() },
    ],
};

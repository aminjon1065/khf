import { Head, useForm } from '@inertiajs/react';
import { FileText, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpRichTextField } from '@/components/admin/cp/fields';
import {
    CpLocaleTabs,
    CpPanel,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import InputError from '@/components/input-error';
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
import { index, store, update } from '@/routes/admin/guides';

type Translation = {
    title: string;
    slug: string;
    summary: string;
    content: string;
    seo_title: string;
    seo_description: string;
};

type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };
type ExistingFile = { id: number; name: string; size: string; url: string };

type GuideData = {
    id: number;
    hazard_type: string | null;
    audience: string;
    status: string;
    sort_order: number;
    translations: Record<string, Partial<Translation>>;
    files: ExistingFile[];
};

type PageProps = {
    guide: GuideData | null;
    hazardTypes: Option[];
    audiences: Option[];
    statuses: Option[];
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function GuideForm({
    guide,
    hazardTypes,
    audiences,
    statuses,
    locales,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(guide);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = guide?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            summary: existing?.summary ?? '',
            content: existing?.content ?? '',
            seo_title: existing?.seo_title ?? '',
            seo_description: existing?.seo_description ?? '',
        };
    });

    const form = useForm({
        hazard_type: guide?.hazard_type ?? '',
        audience: guide?.audience ?? audiences[0]?.value ?? '',
        status: guide?.status ?? 'draft',
        sort_order: guide?.sort_order ?? 0,
        files: [] as File[],
        remove_files: [] as number[],
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;
    const existingFiles = (guide?.files ?? []).filter(
        (file) => !form.data.remove_files.includes(file.id),
    );

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

        if (isEdit && guide) {
            form.put(update(guide.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование памятки' : 'Новая памятка';

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
                            <div className="space-y-2">
                                <Label htmlFor="hazard_type">Тип ЧС</Label>
                                <Select
                                    value={
                                        form.data.hazard_type
                                            ? form.data.hazard_type
                                            : 'none'
                                    }
                                    onValueChange={(value) =>
                                        form.setData(
                                            'hazard_type',
                                            value === 'none' ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger id="hazard_type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Без привязки
                                        </SelectItem>
                                        {hazardTypes.map((hazardType) => (
                                            <SelectItem
                                                key={hazardType.value}
                                                value={hazardType.value}
                                            >
                                                {hazardType.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.hazard_type} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="audience">Аудитория</Label>
                                <Select
                                    value={form.data.audience}
                                    onValueChange={(value) =>
                                        form.setData('audience', value)
                                    }
                                >
                                    <SelectTrigger id="audience">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {audiences.map((audience) => (
                                            <SelectItem
                                                key={audience.value}
                                                value={audience.value}
                                            >
                                                {audience.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.audience} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="sort_order">Порядок</Label>
                                <Input
                                    id="sort_order"
                                    type="number"
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

                        <CpPanel title="Файлы">
                            {existingFiles.length > 0 && (
                                <ul className="space-y-2">
                                    {existingFiles.map((file) => (
                                        <li
                                            key={file.id}
                                            className="flex items-center gap-3 rounded-md border border-border p-2 text-sm"
                                        >
                                            <FileText className="size-4 text-muted-foreground" />
                                            <a
                                                href={file.url}
                                                className="min-w-0 flex-1 truncate text-primary hover:underline"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                {file.name}
                                            </a>
                                            <span className="text-muted-foreground">
                                                {file.size}
                                            </span>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Убрать файл"
                                                onClick={() =>
                                                    form.setData(
                                                        'remove_files',
                                                        [
                                                            ...form.data
                                                                .remove_files,
                                                            file.id,
                                                        ],
                                                    )
                                                }
                                            >
                                                <X className="size-4" />
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            <Input
                                type="file"
                                multiple
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                onChange={(event) =>
                                    form.setData(
                                        'files',
                                        Array.from(event.target.files ?? []),
                                    )
                                }
                            />
                            <InputError
                                message={errors['files.0'] ?? errors.files}
                            />
                            <p className="text-xs text-muted-foreground">
                                До 20 МБ на файл. PDF, Word, изображения.
                            </p>
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
                        placeholder="Заголовок памятки"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={errors[`translations.${activeLocale}.title`]}
                    />
                </div>

                <CpPanel title="Содержание">
                    <div className="space-y-2">
                        <Label htmlFor="slug">Ссылка (slug)</Label>
                        <Input
                            id="slug"
                            value={active.slug}
                            placeholder="оставьте пустым для авто"
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
                    <div className="space-y-2">
                        <Label htmlFor="summary">Краткое описание</Label>
                        <Input
                            id="summary"
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
                    <CpRichTextField
                        label="Содержание"
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
                        <Label htmlFor="seo_title">SEO-заголовок</Label>
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
                        <Label htmlFor="seo_description">SEO-описание</Label>
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

GuideForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Памятки', href: index() },
    ],
};

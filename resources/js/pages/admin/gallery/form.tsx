import { Head, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
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
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/gallery';

type Translation = { title: string; slug: string; description: string };
type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };
type ExistingPhoto = { id: number; url: string; name: string };

type GalleryData = {
    id: number;
    status: string;
    sort_order: number;
    translations: Record<string, Partial<Translation>>;
    photos: ExistingPhoto[];
};

type PageProps = {
    gallery: GalleryData | null;
    statuses: Option[];
    locales: LocaleOption[];
    defaultLocale: string;
};

const emptyTranslation: Translation = { title: '', slug: '', description: '' };

export default function GalleryForm({
    gallery,
    statuses,
    locales,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(gallery);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = gallery?.translations?.[locale.code];
        initialTranslations[locale.code] = { ...emptyTranslation, ...existing };
    });

    const form = useForm({
        status: gallery?.status ?? statuses[0]?.value ?? 'draft',
        sort_order: gallery?.sort_order ?? 0,
        photos: [] as File[],
        remove_photos: [] as number[],
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;
    const existingPhotos = (gallery?.photos ?? []).filter(
        (photo) => !form.data.remove_photos.includes(photo.id),
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

        if (isEdit && gallery) {
            form.put(update(gallery.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование галереи' : 'Новая галерея';

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
                        aria-label="Название"
                        value={active.title}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'title',
                                event.target.value,
                            )
                        }
                        placeholder="Название галереи"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={errors[`translations.${activeLocale}.title`]}
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
                            placeholder="оставьте пустым для авто"
                        />
                        <InputError
                            message={
                                errors[`translations.${activeLocale}.slug`]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">Описание</Label>
                        <Textarea
                            id="description"
                            rows={3}
                            value={active.description}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'description',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                errors[
                                    `translations.${activeLocale}.description`
                                ]
                            }
                        />
                    </div>
                </CpPanel>

                <CpPanel title="Фотографии">
                    {existingPhotos.length > 0 && (
                        <div className="grid grid-cols-3 gap-3 sm:grid-cols-4">
                            {existingPhotos.map((photo) => (
                                <div
                                    key={photo.id}
                                    className="group relative overflow-hidden rounded-md border"
                                >
                                    <img
                                        src={photo.url}
                                        alt={photo.name}
                                        className="aspect-square w-full object-cover"
                                    />
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="icon"
                                        aria-label="Убрать фото"
                                        className="absolute top-1 right-1 size-6"
                                        onClick={() =>
                                            form.setData('remove_photos', [
                                                ...form.data.remove_photos,
                                                photo.id,
                                            ])
                                        }
                                    >
                                        <X className="size-3.5" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    )}
                    <Input
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        onChange={(event) =>
                            form.setData(
                                'photos',
                                Array.from(event.target.files ?? []),
                            )
                        }
                    />
                    <InputError message={errors['photos.0'] ?? errors.photos} />
                    <p className="text-xs text-muted-foreground">
                        JPG, PNG, GIF, WebP. До 5 МБ на изображение.
                    </p>
                </CpPanel>
            </CpPublishForm>
        </>
    );
}

GalleryForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Фотогалерея', href: index() },
    ],
};

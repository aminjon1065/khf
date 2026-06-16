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
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/leadership';

type Translation = {
    full_name: string;
    position: string;
    bio: string;
    reception: string;
};

type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };

type LeaderData = {
    id: number;
    status: string;
    sort_order: number;
    email: string | null;
    phone: string | null;
    photo_url: string | null;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    leader: LeaderData | null;
    locales: LocaleOption[];
    statuses: Option[];
    defaultLocale: string;
};

const emptyTranslation: Translation = {
    full_name: '',
    position: '',
    bio: '',
    reception: '',
};

export default function LeaderForm({
    leader,
    locales,
    statuses,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(leader);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = leader?.translations?.[locale.code];
        initialTranslations[locale.code] = { ...emptyTranslation, ...existing };
    });

    const form = useForm({
        status: leader?.status ?? statuses[0]?.value ?? 'draft',
        sort_order: leader?.sort_order ?? 0,
        email: leader?.email ?? '',
        phone: leader?.phone ?? '',
        photo: null as File | null,
        remove_photo: false,
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

        if (isEdit && leader) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(update(leader.id).url, {
                preserveScroll: true,
                forceFormData: true,
            });
        } else {
            form.post(store().url, {
                preserveScroll: true,
                forceFormData: true,
            });
        }
    };

    const active = form.data.translations[activeLocale];
    const showCurrentPhoto =
        leader?.photo_url && !form.data.photo && !form.data.remove_photo;
    const title = isEdit ? 'Редактирование руководителя' : 'Новый руководитель';

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

                        <CpPanel title="Фотография">
                            {showCurrentPhoto && (
                                <img
                                    src={leader!.photo_url!}
                                    alt=""
                                    className="size-24 rounded-lg object-cover"
                                />
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="photo">Загрузить фото</Label>
                                <Input
                                    id="photo"
                                    type="file"
                                    accept="image/*"
                                    onChange={(event) =>
                                        form.setData({
                                            ...form.data,
                                            photo:
                                                event.target.files?.[0] ?? null,
                                            remove_photo: false,
                                        })
                                    }
                                />
                                <InputError message={errors.photo} />
                            </div>
                            {leader?.photo_url && !form.data.photo && (
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={form.data.remove_photo}
                                        onChange={(event) =>
                                            form.setData(
                                                'remove_photo',
                                                event.target.checked,
                                            )
                                        }
                                    />
                                    Удалить текущее фото
                                </label>
                            )}
                        </CpPanel>

                        <CpPanel title="Контакты">
                            <div className="space-y-2">
                                <Label htmlFor="email">E-mail</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="phone">Телефон</Label>
                                <Input
                                    id="phone"
                                    value={form.data.phone}
                                    onChange={(event) =>
                                        form.setData(
                                            'phone',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.phone} />
                            </div>
                        </CpPanel>
                    </>
                }
            >
                <CpLocaleTabs
                    locales={locales}
                    active={activeLocale}
                    onChange={setActiveLocale}
                    isComplete={(code) =>
                        Boolean(form.data.translations[code]?.full_name)
                    }
                />

                <div>
                    <input
                        aria-label="ФИО"
                        value={active.full_name}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'full_name',
                                event.target.value,
                            )
                        }
                        placeholder="Фамилия Имя Отчество"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={
                            errors[`translations.${activeLocale}.full_name`]
                        }
                    />
                </div>

                <CpPanel title="Основное">
                    <div className="space-y-2">
                        <Label htmlFor="position">Должность</Label>
                        <Input
                            id="position"
                            value={active.position}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'position',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            message={
                                errors[`translations.${activeLocale}.position`]
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="reception">График приёма граждан</Label>
                        <Textarea
                            id="reception"
                            rows={2}
                            value={active.reception}
                            onChange={(event) =>
                                setTranslation(
                                    activeLocale,
                                    'reception',
                                    event.target.value,
                                )
                            }
                            placeholder="Например: понедельник, среда 9:00–12:00"
                        />
                        <InputError
                            message={
                                errors[`translations.${activeLocale}.reception`]
                            }
                        />
                    </div>
                    <CpRichTextField
                        label="Биография"
                        editorKey={`${activeLocale}-bio`}
                        value={active.bio}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'bio', html)
                        }
                        error={errors[`translations.${activeLocale}.bio`]}
                    />
                </CpPanel>
            </CpPublishForm>
        </>
    );
}

LeaderForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Руководство', href: index() },
    ],
};

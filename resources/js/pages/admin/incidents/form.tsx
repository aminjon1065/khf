import { Head, Link, useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState  } from 'react';
import type {FormEvent} from 'react';
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
import { index, store, update } from '@/routes/admin/incidents';

type Translation = { title: string; description: string };
type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };
type RegionOption = { id: number; name: string };

type IncidentData = {
    id: number;
    type: string;
    hazard_level: string;
    status: string;
    region_id: number | null;
    latitude: number | null;
    longitude: number | null;
    occurred_at: string | null;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    incident: IncidentData | null;
    types: Option[];
    levels: Option[];
    statuses: Option[];
    regions: RegionOption[];
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function IncidentForm({ incident, types, levels, statuses, regions, locales, defaultLocale }: PageProps) {
    const isEdit = Boolean(incident);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = incident?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            description: existing?.description ?? '',
        };
    });

    const form = useForm({
        type: incident?.type ?? types[0]?.value ?? '',
        hazard_level: incident?.hazard_level ?? levels[0]?.value ?? '',
        status: incident?.status ?? statuses[0]?.value ?? 'active',
        region_id: incident?.region_id ?? null,
        latitude: incident?.latitude ?? '',
        longitude: incident?.longitude ?? '',
        occurred_at: incident?.occurred_at ?? '',
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

        if (isEdit && incident) {
            form.put(update(incident.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];

    return (
        <>
            <Head title={isEdit ? 'Редактирование события' : 'Новое событие'} />

            <form onSubmit={submit} className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{isEdit ? 'Редактирование события' : 'Новое событие ЧС'}</h1>
                    <div className="flex gap-2">
                        <Button type="button" variant="outline" asChild>
                            <Link href={index().url}>Отмена</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Сохранить
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                        <Label htmlFor="hazard_level">Уровень опасности</Label>
                        <Select value={form.data.hazard_level} onValueChange={(value) => form.setData('hazard_level', value)}>
                            <SelectTrigger id="hazard_level">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {levels.map((level) => (
                                    <SelectItem key={level.value} value={level.value}>
                                        {level.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.hazard_level} />
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
                        <Label htmlFor="region">Регион</Label>
                        <Select
                            value={form.data.region_id ? String(form.data.region_id) : 'none'}
                            onValueChange={(value) => form.setData('region_id', value === 'none' ? null : Number(value))}
                        >
                            <SelectTrigger id="region">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">— Не указан —</SelectItem>
                                {regions.map((region) => (
                                    <SelectItem key={region.id} value={String(region.id)}>
                                        {region.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.region_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="occurred_at">Дата и время</Label>
                        <Input
                            id="occurred_at"
                            type="datetime-local"
                            value={form.data.occurred_at}
                            onChange={(event) => form.setData('occurred_at', event.target.value)}
                        />
                        <InputError message={errors.occurred_at} />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:max-w-md">
                    <div className="space-y-2">
                        <Label htmlFor="latitude">Широта</Label>
                        <Input
                            id="latitude"
                            type="number"
                            step="0.0000001"
                            value={form.data.latitude}
                            onChange={(event) => form.setData('latitude', event.target.value)}
                        />
                        <InputError message={errors.latitude} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="longitude">Долгота</Label>
                        <Input
                            id="longitude"
                            type="number"
                            step="0.0000001"
                            value={form.data.longitude}
                            onChange={(event) => form.setData('longitude', event.target.value)}
                        />
                        <InputError message={errors.longitude} />
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
                        <Label htmlFor="description">Описание</Label>
                        <Textarea
                            id="description"
                            rows={6}
                            value={active.description}
                            onChange={(event) => setTranslation(activeLocale, 'description', event.target.value)}
                        />
                        <InputError message={errors[`translations.${activeLocale}.description`]} />
                    </div>
                </div>
            </form>
        </>
    );
}

IncidentForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'События ЧС', href: index() },
    ],
};

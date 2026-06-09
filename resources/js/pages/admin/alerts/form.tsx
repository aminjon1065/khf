import { Head, Link, useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState  } from 'react';
import type {FormEvent} from 'react';
import InputError from '@/components/input-error';
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
import { index, store, update } from '@/routes/admin/alerts';

type Translation = { title: string; body: string };
type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };
type RegionOption = { id: number; name: string };

type AlertData = {
    id: number;
    hazard_level: string;
    status: string;
    region_id: number | null;
    is_dismissible: boolean;
    starts_at: string | null;
    ends_at: string | null;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    alert: AlertData | null;
    levels: Option[];
    statuses: Option[];
    regions: RegionOption[];
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function AlertForm({ alert, levels, statuses, regions, locales, defaultLocale }: PageProps) {
    const isEdit = Boolean(alert);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = alert?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            body: existing?.body ?? '',
        };
    });

    const form = useForm({
        hazard_level: alert?.hazard_level ?? levels[0]?.value ?? '',
        status: alert?.status ?? statuses[0]?.value ?? 'draft',
        region_id: alert?.region_id ?? null,
        is_dismissible: alert?.is_dismissible ?? true,
        starts_at: alert?.starts_at ?? '',
        ends_at: alert?.ends_at ?? '',
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

        if (isEdit && alert) {
            form.put(update(alert.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];

    return (
        <>
            <Head title={isEdit ? 'Редактирование оповещения' : 'Новое оповещение'} />

            <form onSubmit={submit} className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{isEdit ? 'Редактирование оповещения' : 'Новое оповещение'}</h1>
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
                                <SelectItem value="none">Вся страна</SelectItem>
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
                        <Label htmlFor="starts_at">Начало</Label>
                        <Input
                            id="starts_at"
                            type="datetime-local"
                            value={form.data.starts_at}
                            onChange={(event) => form.setData('starts_at', event.target.value)}
                        />
                        <InputError message={errors.starts_at} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="ends_at">Окончание</Label>
                        <Input
                            id="ends_at"
                            type="datetime-local"
                            value={form.data.ends_at}
                            onChange={(event) => form.setData('ends_at', event.target.value)}
                        />
                        <InputError message={errors.ends_at} />
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <Checkbox
                        id="is_dismissible"
                        checked={form.data.is_dismissible}
                        onCheckedChange={(checked) => form.setData('is_dismissible', checked === true)}
                    />
                    <Label htmlFor="is_dismissible">Пользователь может закрыть баннер</Label>
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
                        <Label htmlFor="body">Текст</Label>
                        <Textarea
                            id="body"
                            rows={4}
                            value={active.body}
                            onChange={(event) => setTranslation(activeLocale, 'body', event.target.value)}
                        />
                        <InputError message={errors[`translations.${activeLocale}.body`]} />
                    </div>
                </div>
            </form>
        </>
    );
}

AlertForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Оповещения', href: index() },
    ],
};

import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    CpSelectField,
    CpTextField,
    CpTextareaField,
    CpToggleField,
} from '@/components/admin/cp/fields';
import {
    CpLocaleTabs,
    CpPanel,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { CpRelationField } from '@/components/admin/cp/relation-field';
import InputError from '@/components/input-error';
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

export default function AlertForm({
    alert,
    levels,
    statuses,
    regions,
    locales,
    defaultLocale,
}: PageProps) {
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

    const [showModal, setShowModal] = useState(false);
    const [estimatedCount, setEstimatedCount] = useState<number | null>(null);
    const [isEstimating, setIsEstimating] = useState(false);

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

    const doSubmit = () => {
        if (isEdit && alert) {
            form.put(update(alert.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
        setShowModal(false);
    };

    const submit = async (event: FormEvent) => {
        event.preventDefault();

        if (form.data.status === 'published' && alert?.status !== 'published') {
            setIsEstimating(true);
            setShowModal(true);
            try {
                const url = new URL(
                    '/admin/alerts/estimate',
                    window.location.origin,
                );
                if (form.data.region_id) {
                    url.searchParams.set(
                        'region_id',
                        String(form.data.region_id),
                    );
                }
                const response = await fetch(url.toString());
                const data = await response.json();
                setEstimatedCount(data.count);
            } catch (error) {
                console.error('Failed to estimate recipients', error);
                setEstimatedCount(0);
            } finally {
                setIsEstimating(false);
            }
            return;
        }

        doSubmit();
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование оповещения' : 'Новое оповещение';

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                modelInfo={{ type: 'alert', id: alert?.id ?? null }}
                sidebar={
                    <CpPanel title="Параметры">
                        <CpSelectField
                            id="hazard_level"
                            label="Уровень опасности"
                            value={form.data.hazard_level}
                            onChange={(value) =>
                                form.setData('hazard_level', value)
                            }
                            options={levels}
                            error={errors.hazard_level}
                        />
                        <CpSelectField
                            id="status"
                            label="Статус"
                            value={form.data.status}
                            onChange={(value) => form.setData('status', value)}
                            options={statuses}
                            error={errors.status}
                        />
                        <CpRelationField
                            id="region"
                            label="Регион"
                            value={form.data.region_id}
                            options={regions}
                            onChange={(value) =>
                                form.setData('region_id', value)
                            }
                            placeholder="Вся страна"
                            error={errors.region_id}
                        />
                        <CpTextField
                            id="starts_at"
                            label="Начало"
                            type="datetime-local"
                            value={form.data.starts_at}
                            onChange={(value) =>
                                form.setData('starts_at', value)
                            }
                            error={errors.starts_at}
                        />
                        <CpTextField
                            id="ends_at"
                            label="Окончание"
                            type="datetime-local"
                            value={form.data.ends_at}
                            onChange={(value) => form.setData('ends_at', value)}
                            error={errors.ends_at}
                        />
                        <CpToggleField
                            id="is_dismissible"
                            label="Пользователь может закрыть баннер"
                            instructions="Снимите для критических оповещений, которые нельзя скрыть."
                            checked={form.data.is_dismissible}
                            onChange={(value) =>
                                form.setData('is_dismissible', value)
                            }
                        />
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
                        placeholder="Заголовок оповещения"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={errors[`translations.${activeLocale}.title`]}
                    />
                </div>

                <CpPanel title="Текст">
                    <CpTextareaField
                        id="body"
                        label="Текст оповещения"
                        rows={5}
                        value={active.body}
                        onChange={(value) =>
                            setTranslation(activeLocale, 'body', value)
                        }
                        error={errors[`translations.${activeLocale}.body`]}
                    />
                </CpPanel>
            </CpPublishForm>

            <Dialog open={showModal} onOpenChange={setShowModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Подтверждение публикации</DialogTitle>
                        <DialogDescription>
                            Вы собираетесь опубликовать это оповещение. После
                            публикации оно будет немедленно отправлено
                            подписчикам через Email и Push-уведомления. Отменить
                            эту операцию невозможно.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="py-4">
                        <p className="text-sm font-medium">
                            Оценочное количество получателей:
                        </p>
                        <p className="mt-1 text-3xl font-bold text-primary">
                            {isEstimating ? '...' : (estimatedCount ?? 0)}
                        </p>
                        <p className="mt-2 text-xs text-muted-foreground">
                            *Учитываются подтвержденные подписчики, выбравшие
                            тему «Оповещения» и подходящие по региону.
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setShowModal(false)}
                            disabled={form.processing}
                        >
                            Отмена
                        </Button>
                        <Button
                            type="button"
                            onClick={doSubmit}
                            disabled={isEstimating || form.processing}
                        >
                            {form.processing
                                ? 'Публикация...'
                                : 'Опубликовать и отправить'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

AlertForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Оповещения', href: index() },
    ],
};

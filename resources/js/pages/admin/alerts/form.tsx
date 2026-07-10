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
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import {
    CpLocaleTabs,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/alerts';
import type { BlueprintDefinition, BlueprintFieldOptions } from '@/types/cms';

type Translation = { title: string; body: string };
type LocaleOption = { code: string; native_name: string };

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
    blueprint: BlueprintDefinition;
    fieldOptions: BlueprintFieldOptions;
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function AlertForm({
    alert,
    blueprint,
    fieldOptions,
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
        hazard_level: alert?.hazard_level ?? '',
        status: alert?.status ?? 'draft',
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

    const title = isEdit ? 'Редактирование оповещения' : 'Новое оповещение';
    const formMeta = {
        statuses: [],
        statusTransitions: [],
    };

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
                    <CpBlueprintForm
                        blueprint={blueprint}
                        section="sidebar"
                        data={form.data}
                        errors={errors}
                        activeLocale={activeLocale}
                        fieldOptions={fieldOptions}
                        meta={formMeta}
                        onRootChange={(handle, value) =>
                            form.setData(handle as keyof typeof form.data, value as never)
                        }
                        onTranslationChange={(locale, handle, value) =>
                            form.setData('translations', {
                                ...form.data.translations,
                                [locale]: {
                                    ...form.data.translations[locale],
                                    [handle]: value,
                                },
                            })
                        }
                        onAssetChange={(patch) =>
                            form.setData({ ...form.data, ...patch })
                        }
                    />
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

                <CpBlueprintForm
                    blueprint={blueprint}
                    section="main"
                    data={form.data}
                    errors={errors}
                    activeLocale={activeLocale}
                    fieldOptions={fieldOptions}
                    meta={formMeta}
                    titleAsHeader
                    titleFieldHandle="title"
                    onRootChange={(handle, value) =>
                        form.setData(handle as keyof typeof form.data, value as never)
                    }
                    onTranslationChange={(locale, handle, value) =>
                        form.setData('translations', {
                            ...form.data.translations,
                            [locale]: {
                                ...form.data.translations[locale],
                                [handle]: value,
                            },
                        })
                    }
                    onAssetChange={(patch) =>
                        form.setData({ ...form.data, ...patch })
                    }
                />
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

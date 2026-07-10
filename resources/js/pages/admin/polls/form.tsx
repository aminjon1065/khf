import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import {
    CpPollOptionsField,
    emptyPollOption,
} from '@/components/admin/cp/poll-options-field';
import {
    CpLocaleTabs,
    CpPanel,
    CpPublishForm,
} from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/polls';
import type {
    BlueprintDefinition,
    BlueprintFieldOptions,
    SelectOption,
} from '@/types/cms';

type Translation = { title: string; description: string; slug: string };
type OptionTranslation = { label: string };
type Option = {
    id?: number;
    sort_order: number;
    votes_count?: number;
    translations: Record<string, OptionTranslation>;
};
type LocaleOption = { code: string; native_name: string };

type PollData = {
    id: number;
    type: string;
    status: string;
    starts_at: string | null;
    ends_at: string | null;
    show_results: boolean;
    sort_order: number;
    total_votes?: number;
    translations: Record<string, Partial<Translation>>;
    options: Option[];
};

type PageProps = {
    poll: PollData | null;
    blueprint: BlueprintDefinition;
    fieldOptions: BlueprintFieldOptions;
    locales: LocaleOption[];
    statuses: SelectOption[];
    statusTransitions: SelectOption[];
    defaultLocale: string;
};

export default function PollForm({
    poll,
    blueprint,
    fieldOptions,
    locales,
    statuses,
    statusTransitions,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(poll);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = poll?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            title: existing?.title ?? '',
            description: existing?.description ?? '',
            slug: existing?.slug ?? '',
        };
    });

    const initialOptions: Option[] =
        poll?.options && poll.options.length > 0
            ? poll.options.map((option, index) => ({
                  id: option.id,
                  sort_order: option.sort_order ?? index,
                  votes_count: option.votes_count,
                  translations: locales.reduce((acc, locale) => {
                      acc[locale.code] = {
                          label: option.translations?.[locale.code]?.label ?? '',
                      };
                      return acc;
                  }, {} as Record<string, OptionTranslation>),
              }))
            : [emptyPollOption(locales, 0), emptyPollOption(locales, 1)];

    const form = useForm({
        status: poll?.status ?? statuses[0]?.value ?? 'draft',
        type: poll?.type ?? '',
        starts_at: poll?.starts_at ?? '',
        ends_at: poll?.ends_at ?? '',
        show_results: poll?.show_results ?? true,
        sort_order: poll?.sort_order ?? 0,
        translations: initialTranslations,
        options: initialOptions,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && poll) {
            form.put(update(poll.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const title = isEdit ? 'Редактирование опроса' : 'Новый опрос';
    const formMeta = {
        statuses,
        statusTransitions,
        showSchedule: true,
    };

    return (
        <>
            <Head title={title} />

            <CpPublishForm
                title={title}
                backHref={index().url}
                onSubmit={submit}
                processing={form.processing}
                saveLabel={poll?.id ? 'Обновить' : 'Создать'}
                modelInfo={{ type: 'poll', id: poll?.id ?? null }}
                sidebar={
                    <CpBlueprintForm
                        blueprint={blueprint}
                        section="sidebar"
                        data={form.data}
                        errors={errors}
                        activeLocale={activeLocale}
                        fieldOptions={fieldOptions}
                        meta={formMeta}
                        excludeHandles={['options']}
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
                    excludeHandles={['options']}
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

                <CpPanel title="Варианты ответа">
                    <CpPollOptionsField
                        locales={locales}
                        activeLocale={activeLocale}
                        options={form.data.options}
                        totalVotes={poll?.total_votes}
                        errors={errors}
                        onChange={(options) => form.setData('options', options)}
                    />
                </CpPanel>
            </CpPublishForm>
        </>
    );
}

PollForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Опросы', href: index() },
    ],
};

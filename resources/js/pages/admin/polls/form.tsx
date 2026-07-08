import { Head, useForm } from '@inertiajs/react';
import { Minus, Plus } from 'lucide-react';
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
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/polls';

type Translation = { title: string; description: string; slug: string };
type OptionTranslation = { label: string };
type Option = {
    id?: number;
    sort_order: number;
    votes_count?: number;
    translations: Record<string, OptionTranslation>;
};
type OptionField = { value: string; label: string };

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
    types: OptionField[];
    statuses: OptionField[];
    locales: { code: string; native_name: string }[];
    defaultLocale: string;
};

function emptyOption(
    locales: { code: string }[],
    sortOrder: number,
): Option {
    const translations: Record<string, OptionTranslation> = {};
    locales.forEach((locale) => {
        translations[locale.code] = { label: '' };
    });

    return { sort_order: sortOrder, translations };
}

export default function PollForm({
    poll,
    types,
    statuses,
    locales,
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
                  translations: locales.reduce(
                      (acc, locale) => {
                          acc[locale.code] = {
                              label:
                                  option.translations?.[locale.code]?.label ??
                                  '',
                          };
                          return acc;
                      },
                      {} as Record<string, OptionTranslation>,
                  ),
              }))
            : [emptyOption(locales, 0), emptyOption(locales, 1)];

    const form = useForm({
        type: poll?.type ?? types[0]?.value ?? 'general',
        status: poll?.status ?? statuses[0]?.value ?? 'draft',
        starts_at: poll?.starts_at ?? '',
        ends_at: poll?.ends_at ?? '',
        show_results: poll?.show_results ?? true,
        sort_order: poll?.sort_order ?? 0,
        translations: initialTranslations,
        options: initialOptions,
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

    const setOptionLabel = (
        optionIndex: number,
        locale: string,
        value: string,
    ) => {
        const options = [...form.data.options];
        options[optionIndex] = {
            ...options[optionIndex],
            translations: {
                ...options[optionIndex].translations,
                [locale]: { label: value },
            },
        };
        form.setData('options', options);
    };

    const addOption = () => {
        form.setData('options', [
            ...form.data.options,
            emptyOption(locales, form.data.options.length),
        ]);
    };

    const removeOption = (index: number) => {
        if (form.data.options.length <= 2) {
            return;
        }

        form.setData(
            'options',
            form.data.options.filter((_, i) => i !== index),
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit && poll) {
            form.put(update(poll.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование опроса' : 'Новый опрос';

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
                                <Label htmlFor="starts_at">Начало</Label>
                                <Input
                                    id="starts_at"
                                    type="datetime-local"
                                    value={form.data.starts_at ?? ''}
                                    onChange={(event) =>
                                        form.setData(
                                            'starts_at',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.starts_at} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="ends_at">Окончание</Label>
                                <Input
                                    id="ends_at"
                                    type="datetime-local"
                                    value={form.data.ends_at ?? ''}
                                    onChange={(event) =>
                                        form.setData(
                                            'ends_at',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.ends_at} />
                            </div>
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id="show_results"
                                    checked={form.data.show_results}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'show_results',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label htmlFor="show_results">
                                    Показывать результаты
                                </Label>
                            </div>
                            {isEdit && poll?.total_votes !== undefined && (
                                <p className="text-sm text-muted-foreground">
                                    Всего голосов: {poll.total_votes}
                                </p>
                            )}
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
                        aria-label="Название опроса"
                        value={active.title}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'title',
                                event.target.value,
                            )
                        }
                        placeholder="Название опроса"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={
                            errors[`translations.${activeLocale}.title`]
                        }
                    />
                </div>

                <CpPanel title="Описание">
                    <CpRichTextField
                        label="Текст опроса"
                        editorKey={`${activeLocale}-description`}
                        value={active.description}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'description', html)
                        }
                        error={
                            errors[`translations.${activeLocale}.description`]
                        }
                    />
                </CpPanel>

                <CpPanel title="Варианты ответа">
                    <div className="space-y-4">
                        {form.data.options.map((option, optionIndex) => (
                            <div
                                key={optionIndex}
                                className="flex items-start gap-3 rounded-lg border p-4"
                            >
                                <div className="flex-1 space-y-2">
                                    <Label>
                                        Вариант {optionIndex + 1}
                                        {option.votes_count !== undefined && (
                                            <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                ({option.votes_count} гол.)
                                            </span>
                                        )}
                                    </Label>
                                    <Input
                                        value={
                                            option.translations[activeLocale]
                                                ?.label ?? ''
                                        }
                                        onChange={(event) =>
                                            setOptionLabel(
                                                optionIndex,
                                                activeLocale,
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Текст варианта"
                                    />
                                    <InputError
                                        message={
                                            errors[
                                                `options.${optionIndex}.translations.${activeLocale}.label`
                                            ]
                                        }
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Удалить вариант"
                                    disabled={form.data.options.length <= 2}
                                    onClick={() => removeOption(optionIndex)}
                                >
                                    <Minus className="size-4" />
                                </Button>
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addOption}
                            disabled={form.data.options.length >= 20}
                        >
                            <Plus className="size-4" />
                            Добавить вариант
                        </Button>
                        <InputError message={errors.options} />
                    </div>
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

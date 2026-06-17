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
import { dashboard } from '@/routes/admin';
import { index, store, update } from '@/routes/admin/faqs';

type Translation = { question: string; answer: string };
type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };

type FaqData = {
    id: number;
    status: string;
    sort_order: number;
    translations: Record<string, Partial<Translation>>;
};

type PageProps = {
    faq: FaqData | null;
    statuses: Option[];
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function FaqForm({
    faq,
    statuses,
    locales,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(faq);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = faq?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            question: existing?.question ?? '',
            answer: existing?.answer ?? '',
        };
    });

    const form = useForm({
        status: faq?.status ?? statuses[0]?.value ?? 'draft',
        sort_order: faq?.sort_order ?? 0,
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

        if (isEdit && faq) {
            form.put(update(faq.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование вопроса' : 'Новый вопрос';

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
                        Boolean(form.data.translations[code]?.question)
                    }
                />

                <div>
                    <input
                        aria-label="Вопрос"
                        value={active.question}
                        onChange={(event) =>
                            setTranslation(
                                activeLocale,
                                'question',
                                event.target.value,
                            )
                        }
                        placeholder="Текст вопроса"
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={
                            errors[`translations.${activeLocale}.question`]
                        }
                    />
                </div>

                <CpPanel title="Ответ">
                    <CpRichTextField
                        label="Текст ответа"
                        editorKey={`${activeLocale}-answer`}
                        value={active.answer}
                        onChange={(html) =>
                            setTranslation(activeLocale, 'answer', html)
                        }
                        error={errors[`translations.${activeLocale}.answer`]}
                    />
                </CpPanel>
            </CpPublishForm>
        </>
    );
}

FaqForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Вопросы и ответы', href: index() },
    ],
};

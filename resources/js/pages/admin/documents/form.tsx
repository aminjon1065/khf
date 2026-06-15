import { Head, useForm } from '@inertiajs/react';
import { FileText, X } from 'lucide-react';
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
import { index, store, update } from '@/routes/admin/documents';

type Translation = { name: string; description: string };
type Option = { value: string; label: string };
type LocaleOption = { code: string; native_name: string };
type ExistingFile = { id: number; name: string; size: string; url: string };

type DocumentData = {
    id: number;
    type: string;
    source: string | null;
    document_date: string | null;
    status: string;
    sort_order: number;
    translations: Record<string, Partial<Translation>>;
    files: ExistingFile[];
};

type PageProps = {
    document: DocumentData | null;
    types: Option[];
    statuses: Option[];
    locales: LocaleOption[];
    defaultLocale: string;
};

export default function DocumentForm({
    document,
    types,
    statuses,
    locales,
    defaultLocale,
}: PageProps) {
    const isEdit = Boolean(document);

    const initialTranslations: Record<string, Translation> = {};
    locales.forEach((locale) => {
        const existing = document?.translations?.[locale.code];
        initialTranslations[locale.code] = {
            name: existing?.name ?? '',
            description: existing?.description ?? '',
        };
    });

    const form = useForm({
        type: document?.type ?? types[0]?.value ?? '',
        source: document?.source ?? '',
        document_date: document?.document_date ?? '',
        status: document?.status ?? statuses[0]?.value ?? 'published',
        sort_order: document?.sort_order ?? 0,
        files: [] as File[],
        remove_files: [] as number[],
        translations: initialTranslations,
    });

    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const errors = form.errors as Record<string, string>;
    const existingFiles = (document?.files ?? []).filter(
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

        if (isEdit && document) {
            form.put(update(document.id).url, { preserveScroll: true });
        } else {
            form.post(store().url, { preserveScroll: true });
        }
    };

    const active = form.data.translations[activeLocale];
    const title = isEdit ? 'Редактирование документа' : 'Новый документ';

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
                                <Label htmlFor="document_date">Дата документа</Label>
                                <Input
                                    id="document_date"
                                    type="date"
                                    value={form.data.document_date}
                                    onChange={(event) => form.setData('document_date', event.target.value)}
                                />
                                <InputError message={errors.document_date} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="source">Орган / источник</Label>
                                <Input
                                    id="source"
                                    value={form.data.source}
                                    onChange={(event) => form.setData('source', event.target.value)}
                                />
                                <InputError message={errors.source} />
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
                                            <span className="text-muted-foreground">{file.size}</span>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Убрать файл"
                                                onClick={() =>
                                                    form.setData('remove_files', [...form.data.remove_files, file.id])
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
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.jpg,.jpeg,.png"
                                onChange={(event) => form.setData('files', Array.from(event.target.files ?? []))}
                            />
                            <InputError message={errors['files.0'] ?? errors.files} />
                            <p className="text-xs text-muted-foreground">
                                До 20 МБ на файл. PDF, Word, Excel, PowerPoint, изображения, архивы.
                            </p>
                        </CpPanel>
                    </>
                }
            >
                <CpLocaleTabs
                    locales={locales}
                    active={activeLocale}
                    onChange={setActiveLocale}
                    isComplete={(code) => Boolean(form.data.translations[code]?.name)}
                />

                <div>
                    <input
                        aria-label="Наименование"
                        value={active.name}
                        onChange={(event) => setTranslation(activeLocale, 'name', event.target.value)}
                        placeholder="Наименование документа"
                        className="w-full border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-sm"
                    />
                    <InputError message={errors[`translations.${activeLocale}.name`]} />
                </div>

                <CpPanel title="Описание">
                    <div className="space-y-2">
                        <Label htmlFor="description">Краткое описание</Label>
                        <Textarea
                            id="description"
                            rows={5}
                            value={active.description}
                            onChange={(event) => setTranslation(activeLocale, 'description', event.target.value)}
                        />
                        <InputError message={errors[`translations.${activeLocale}.description`]} />
                    </div>
                </CpPanel>
            </CpPublishForm>
        </>
    );
}

DocumentForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Документы', href: index() },
    ],
};

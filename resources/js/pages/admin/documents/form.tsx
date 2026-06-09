import { Head, Link, useForm } from '@inertiajs/react';
import { Check, FileText, X } from 'lucide-react';
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

export default function DocumentForm({ document, types, statuses, locales, defaultLocale }: PageProps) {
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
    const existingFiles = (document?.files ?? []).filter((file) => !form.data.remove_files.includes(file.id));

    const setTranslation = (locale: string, field: keyof Translation, value: string) => {
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

    return (
        <>
            <Head title={isEdit ? 'Редактирование документа' : 'Новый документ'} />

            <form onSubmit={submit} className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{isEdit ? 'Редактирование документа' : 'Новый документ'}</h1>
                    <div className="flex gap-2">
                        <Button type="button" variant="outline" asChild>
                            <Link href={index().url}>Отмена</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Сохранить
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                </div>

                <div className="space-y-3">
                    <Label>Файлы</Label>
                    {existingFiles.length > 0 && (
                        <ul className="space-y-2">
                            {existingFiles.map((file) => (
                                <li key={file.id} className="flex items-center gap-3 rounded-md border p-2 text-sm">
                                    <FileText className="size-4 text-muted-foreground" />
                                    <a href={file.url} className="flex-1 text-primary hover:underline" target="_blank" rel="noopener noreferrer">
                                        {file.name}
                                    </a>
                                    <span className="text-muted-foreground">{file.size}</span>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Убрать файл"
                                        onClick={() => form.setData('remove_files', [...form.data.remove_files, file.id])}
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
                    <p className="text-xs text-muted-foreground">До 20 МБ на файл. PDF, Word, Excel, PowerPoint, изображения, архивы.</p>
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
                            {Boolean(form.data.translations[locale.code]?.name) && (
                                <Check className="size-3.5 text-green-600" />
                            )}
                        </Button>
                    ))}
                </div>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">Наименование</Label>
                        <Input
                            id="name"
                            value={active.name}
                            onChange={(event) => setTranslation(activeLocale, 'name', event.target.value)}
                        />
                        <InputError message={errors[`translations.${activeLocale}.name`]} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">Краткое описание</Label>
                        <Textarea
                            id="description"
                            rows={4}
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

DocumentForm.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Документы', href: index() },
    ],
};

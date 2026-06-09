import { Head, router, usePage } from '@inertiajs/react';
import { Download, FileText } from 'lucide-react';
import { useState } from 'react';
import type {Paginator} from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as documentsIndex } from '@/routes/documents';

type DocFile = { id: number; name: string; size: string; url: string };

type DocumentItem = {
    id: number;
    name: string | null;
    description: string | null;
    type_label: string;
    document_date: string | null;
    files: DocFile[];
};

type Option = { value: string; label: string };

type PageProps = {
    documents: Paginator<DocumentItem> & { prev_page_url: string | null; next_page_url: string | null };
    filters: { search: string; type: string | null };
    types: Option[];
};

export default function DocumentsRegistry({ documents, filters, types }: PageProps) {
    const { locale } = usePage().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const apply = (params: Record<string, string | undefined>) => {
        router.get(
            documentsIndex({ locale }).url,
            { search: filters.search || undefined, type: filters.type || undefined, ...params },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Документы" />

            <h1 className="mb-6 text-3xl font-semibold">Документы</h1>

            <form
                className="mb-6 flex flex-col gap-3 sm:flex-row"
                onSubmit={(event) => {
                    event.preventDefault();
                    apply({ search: search || undefined });
                }}
            >
                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Поиск по наименованию…"
                    className="sm:max-w-xs"
                />
                <Select
                    value={filters.type ?? 'all'}
                    onValueChange={(value) => apply({ type: value === 'all' ? undefined : value })}
                >
                    <SelectTrigger className="sm:max-w-xs">
                        <SelectValue placeholder="Тип" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Все типы</SelectItem>
                        {types.map((type) => (
                            <SelectItem key={type.value} value={type.value}>
                                {type.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Button type="submit">Найти</Button>
            </form>

            {documents.data.length === 0 ? (
                <p className="text-muted-foreground">Документы не найдены.</p>
            ) : (
                <div className="space-y-4">
                    {documents.data.map((document) => (
                        <div key={document.id} className="rounded-lg border p-4">
                            <div className="flex items-center gap-2">
                                <Badge variant="secondary">{document.type_label}</Badge>
                                {document.document_date && (
                                    <span className="text-sm text-muted-foreground">{document.document_date}</span>
                                )}
                            </div>
                            <h2 className="mt-2 font-semibold">{document.name}</h2>
                            {document.description && (
                                <p className="mt-1 text-sm text-muted-foreground">{document.description}</p>
                            )}
                            {document.files.length > 0 && (
                                <ul className="mt-3 space-y-1">
                                    {document.files.map((file) => (
                                        <li key={file.id}>
                                            <a
                                                href={file.url}
                                                className="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                                            >
                                                <FileText className="size-4" />
                                                {file.name}
                                                <span className="text-muted-foreground">({file.size})</span>
                                                <Download className="size-3.5" />
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {(documents.prev_page_url || documents.next_page_url) && (
                <div className="mt-8 flex items-center justify-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!documents.prev_page_url}
                        onClick={() => documents.prev_page_url && router.get(documents.prev_page_url)}
                    >
                        Назад
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!documents.next_page_url}
                        onClick={() => documents.next_page_url && router.get(documents.next_page_url)}
                    >
                        Вперёд
                    </Button>
                </div>
            )}
        </>
    );
}

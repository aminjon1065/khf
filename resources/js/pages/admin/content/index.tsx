import { Head, Link, router } from '@inertiajs/react';
import {
    ArchiveRestore,
    ArrowLeft,
    Download,
    Pencil,
    Plus,
    Trash2,
    Upload,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { DataTable } from '@/components/admin/data-table';
import type {
    DataTableColumn,
    DataTableFilters,
    Paginator,
} from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import {
    bulkDestroy,
    exportMethod as exportContent,
    hub as contentHub,
    importMethod as importContent,
    index as contentIndex,
} from '@/routes/admin/content';

type EntryRow = {
    id: number;
    title: string;
    status?: string;
    status_label?: string;
    subtype?: string;
    subtype_label?: string;
    locales: string[];
    updated_at: string | null;
    deleted_at?: string | null;
    edit_url: string;
    destroy_url: string;
    restore_url?: string;
    force_delete_url?: string;
};

type ContentTypeMeta = {
    handle: string;
    label: string;
    icon: string;
    features: string[];
    sortable: string[];
    route_prefix: string;
};

type TypeOption = { handle: string; label: string; url: string };

type StatusOption = { value: string; label: string };

type PageProps = {
    contentType: ContentTypeMeta;
    entries: Paginator<EntryRow>;
    filters: DataTableFilters & { status?: string | null; trashed?: boolean };
    statuses: StatusOption[];
    types: TypeOption[];
    trashedCount: number;
    createUrl: string;
    trashUrl: string | null;
    searchPlaceholder: string;
    showSubtype: boolean;
};

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    published: 'default',
    draft: 'secondary',
    moderation: 'outline',
    archived: 'destructive',
    active: 'destructive',
    controlled: 'outline',
    resolved: 'secondary',
    cancelled: 'destructive',
};

export default function ContentIndex({
    contentType,
    entries,
    filters,
    statuses,
    types,
    trashedCount,
    createUrl,
    trashUrl,
    searchPlaceholder,
    showSubtype,
}: PageProps) {
    const [deleting, setDeleting] = useState<EntryRow | null>(null);
    const [purging, setPurging] = useState<EntryRow | null>(null);
    const [selected, setSelected] = useState<number[]>([]);
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [updateExisting, setUpdateExisting] = useState(false);
    const [importing, setImporting] = useState(false);
    const baseUrl = contentIndex(contentType.handle).url;
    const hasSoftDeletes = contentType.features.includes('soft_deletes');
    const viewingTrash = Boolean(filters.trashed);

    const toggleAll = (checked: boolean) => {
        setSelected(checked ? entries.data.map((row) => row.id) : []);
    };

    const toggleRow = (id: number, checked: boolean) => {
        setSelected((current) =>
            checked
                ? [...current, id]
                : current.filter((value) => value !== id),
        );
    };

    const applyStatus = (status: string) => {
        router.get(
            baseUrl,
            {
                ...filters,
                status: status === 'all' ? undefined : status,
                trashed: viewingTrash ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const switchType = (handle: string) => {
        const target = types.find((type) => type.handle === handle);
        if (target) {
            router.visit(
                viewingTrash ? `${target.url}?trashed=1` : target.url,
            );
        }
    };

    const exportUrl = (format: 'json' | 'csv') => {
        const params = new URLSearchParams();
        params.set('format', format);

        if (filters.search) {
            params.set('search', filters.search);
        }

        if (filters.status) {
            params.set('status', filters.status);
        }

        if (filters.sort) {
            params.set('sort', filters.sort);
        }

        if (filters.direction) {
            params.set('direction', filters.direction);
        }

        if (viewingTrash) {
            params.set('trashed', '1');
        }

        selected.forEach((id) => params.append('ids[]', String(id)));

        return `${exportContent(contentType.handle).url}?${params.toString()}`;
    };

    const submitImport = (event: FormEvent) => {
        event.preventDefault();

        if (!importFile) {
            return;
        }

        setImporting(true);

        router.post(
            importContent(contentType.handle).url,
            {
                file: importFile,
                update_existing: updateExisting,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => {
                    setImporting(false);
                    setImportOpen(false);
                    setImportFile(null);
                    setUpdateExisting(false);
                },
            },
        );
    };

    const bulkDelete = () => {
        if (selected.length === 0) {
            return;
        }

        if (
            !confirm(
                `Переместить ${selected.length} материал(ов) в корзину?`,
            )
        ) {
            return;
        }

        router.post(
            bulkDestroy(contentType.handle).url,
            { ids: selected },
            {
                preserveScroll: true,
                onSuccess: () => setSelected([]),
            },
        );
    };

    const columns: DataTableColumn<EntryRow>[] = [
        ...(hasSoftDeletes && !viewingTrash
            ? [
                  {
                      key: 'select',
                      label: '',
                      render: (row: EntryRow) => (
                          <Checkbox
                              checked={selected.includes(row.id)}
                              onCheckedChange={(checked) =>
                                  toggleRow(row.id, checked === true)
                              }
                              aria-label={`Выбрать «${row.title}»`}
                          />
                      ),
                  } satisfies DataTableColumn<EntryRow>,
              ]
            : []),
        { key: 'title', label: 'Заголовок' },
        ...(showSubtype
            ? [
                  {
                      key: 'subtype',
                      label: 'Тип',
                      className: 'hidden md:table-cell',
                      render: (row: EntryRow) =>
                          row.subtype_label ? (
                              <Badge variant="outline">
                                  {row.subtype_label}
                              </Badge>
                          ) : (
                              '—'
                          ),
                  } satisfies DataTableColumn<EntryRow>,
              ]
            : []),
        {
            key: 'locales',
            label: 'Языки',
            render: (row) => (
                <div className="flex gap-1">
                    {['tj', 'ru', 'en'].map((locale) => {
                        const hasTranslation = row.locales.includes(locale);

                        return (
                            <Badge
                                key={locale}
                                variant={
                                    hasTranslation ? 'default' : 'outline'
                                }
                                className={
                                    hasTranslation
                                        ? 'px-1.5 text-[10px] uppercase'
                                        : 'px-1.5 text-[10px] text-muted-foreground uppercase'
                                }
                            >
                                {locale}
                            </Badge>
                        );
                    })}
                </div>
            ),
        },
        ...(statuses.length > 0
            ? [
                  {
                      key: 'status',
                      label: 'Статус',
                      render: (row: EntryRow) =>
                          row.status ? (
                              <Badge
                                  variant={
                                      statusVariant[row.status] ?? 'secondary'
                                  }
                              >
                                  {row.status_label}
                              </Badge>
                          ) : (
                              '—'
                          ),
                  } satisfies DataTableColumn<EntryRow>,
              ]
            : []),
        viewingTrash
            ? {
                  key: 'deleted_at',
                  label: 'Удалён',
                  className: 'hidden sm:table-cell',
                  render: (row: EntryRow) => row.deleted_at ?? '—',
              }
            : {
                  key: 'updated_at',
                  label: 'Изменена',
                  sortable: contentType.sortable.includes('updated_at'),
                  className: 'hidden sm:table-cell',
              },
    ];

    return (
        <>
            <Head
                title={
                    viewingTrash
                        ? `Корзина — ${contentType.label}`
                        : contentType.label
                }
            />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-3">
                        <div>
                            <h1 className="text-2xl font-semibold">
                                {viewingTrash
                                    ? `Корзина — ${contentType.label}`
                                    : contentType.label}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {viewingTrash
                                    ? 'Удалённые записи можно восстановить или удалить навсегда'
                                    : 'Браузер записей коллекции'}
                            </p>
                        </div>
                        {types.length > 1 && (
                            <Select
                                value={contentType.handle}
                                onValueChange={switchType}
                            >
                                <SelectTrigger className="w-[220px]">
                                    <SelectValue placeholder="Коллекция" />
                                </SelectTrigger>
                                <SelectContent>
                                    {types.map((type) => (
                                        <SelectItem
                                            key={type.handle}
                                            value={type.handle}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {hasSoftDeletes &&
                            !viewingTrash &&
                            selected.length > 0 && (
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={bulkDelete}
                                >
                                    <Trash2 className="size-4" />В корзину (
                                    {selected.length})
                                </Button>
                            )}
                        {viewingTrash ? (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={contentIndex(contentType.handle)}>
                                    <ArrowLeft className="size-4" />К списку
                                </Link>
                            </Button>
                        ) : (
                            trashUrl && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={trashUrl}>
                                        <Trash2 className="size-4" />
                                        Корзина{' '}
                                        {trashedCount > 0 &&
                                            `(${trashedCount})`}
                                    </Link>
                                </Button>
                            )
                        )}
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    paginator={entries}
                    filters={filters}
                    baseUrl={baseUrl}
                    getRowId={(row) => row.id}
                    searchPlaceholder={searchPlaceholder}
                    emptyMessage={
                        viewingTrash ? 'Корзина пуста' : 'Ничего не найдено'
                    }
                    toolbar={
                        <div className="flex flex-wrap items-center gap-2">
                            {statuses.length > 0 && (
                                <Select
                                    value={filters.status ?? 'all'}
                                    onValueChange={applyStatus}
                                >
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Статус" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Все статусы
                                        </SelectItem>
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
                            )}
                            {hasSoftDeletes &&
                                !viewingTrash &&
                                entries.data.length > 0 && (
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Checkbox
                                            checked={
                                                selected.length ===
                                                    entries.data.length &&
                                                entries.data.length > 0
                                            }
                                            onCheckedChange={(checked) =>
                                                toggleAll(checked === true)
                                            }
                                        />
                                        Выбрать все
                                    </div>
                                )}
                            {!viewingTrash && (
                                <>
                                    <Button variant="outline" asChild>
                                        <a href={exportUrl('json')}>
                                            <Download className="size-4" />
                                            JSON
                                        </a>
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <a href={exportUrl('csv')}>
                                            <Download className="size-4" />
                                            CSV
                                        </a>
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setImportOpen(true)}
                                    >
                                        <Upload className="size-4" />
                                        Импорт
                                    </Button>
                                    <Button asChild>
                                        <Link href={createUrl}>
                                            <Plus className="size-4" />
                                            Создать
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    }
                    actions={(row) =>
                        viewingTrash ? (
                            <div className="flex justify-end gap-1">
                                {row.restore_url && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Восстановить"
                                        onClick={() =>
                                            router.patch(
                                                row.restore_url!,
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <ArchiveRestore className="size-4" />
                                    </Button>
                                )}
                                {row.force_delete_url && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Удалить навсегда"
                                        onClick={() => setPurging(row)}
                                    >
                                        <Trash2 className="size-4 text-destructive" />
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Изменить"
                                    asChild
                                >
                                    <Link href={row.edit_url}>
                                        <Pencil className="size-4" />
                                    </Link>
                                </Button>
                                {hasSoftDeletes && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Удалить"
                                        onClick={() => setDeleting(row)}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </div>
                        )
                    }
                />
            </div>

            <Dialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Удалить запись?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.title}» будет перемещена в корзину.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleting(null)}
                        >
                            Отмена
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => {
                                if (deleting) {
                                    router.delete(deleting.destroy_url, {
                                        preserveScroll: true,
                                        onSuccess: () => setDeleting(null),
                                    });
                                }
                            }}
                        >
                            В корзину
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={Boolean(purging)}
                onOpenChange={(open) => !open && setPurging(null)}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Удалить навсегда?</DialogTitle>
                        <DialogDescription>
                            «{purging?.title}» будет удалён безвозвратно.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setPurging(null)}
                        >
                            Отмена
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => {
                                if (purging?.force_delete_url) {
                                    router.delete(purging.force_delete_url, {
                                        preserveScroll: true,
                                        onSuccess: () => setPurging(null),
                                    });
                                }
                            }}
                        >
                            Удалить навсегда
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={importOpen} onOpenChange={setImportOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Импорт коллекции</DialogTitle>
                        <DialogDescription>
                            Загрузите JSON-экспорт из этой CMS или CSV с
                            колонками entry_id, locale, title, slug и др.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitImport} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="import_file">Файл</Label>
                            <Input
                                id="import_file"
                                type="file"
                                accept=".json,.csv,.txt"
                                onChange={(event) =>
                                    setImportFile(
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="update_existing"
                                checked={updateExisting}
                                onCheckedChange={(checked) =>
                                    setUpdateExisting(checked === true)
                                }
                            />
                            <Label htmlFor="update_existing">
                                Обновлять существующие записи по ID
                            </Label>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setImportOpen(false)}
                            >
                                Отмена
                            </Button>
                            <Button
                                type="submit"
                                disabled={importing || importFile === null}
                            >
                                Импортировать
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

ContentIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Коллекции', href: contentHub() },
        { title: 'Записи', href: '#' },
    ],
};

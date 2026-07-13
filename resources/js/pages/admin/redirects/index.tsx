import { Head, router, useForm } from '@inertiajs/react';
import { ArrowRightLeft, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { DataTable } from '@/components/admin/data-table';
import type {
    DataTableColumn,
    DataTableFilters,
    Paginator,
} from '@/components/admin/data-table';
import InputError from '@/components/input-error';
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
import { destroy, index, store, update } from '@/routes/admin/redirects';

type RedirectRow = {
    id: number;
    from_path: string;
    to_url: string;
    status_code: number;
    is_active: boolean;
    notes: string | null;
    created_at: string;
};

type PageProps = {
    redirects: Paginator<RedirectRow>;
    filters: DataTableFilters;
};

export default function RedirectsIndex({ redirects, filters }: PageProps) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<RedirectRow | null>(null);
    const [deleting, setDeleting] = useState<RedirectRow | null>(null);

    const columns: DataTableColumn<RedirectRow>[] = [
        {
            key: 'from_path',
            label: 'Откуда',
            sortable: true,
            render: (redirect) => (
                <code className="text-xs">/{redirect.from_path}</code>
            ),
        },
        {
            key: 'to_url',
            label: 'Куда',
            sortable: true,
            render: (redirect) => (
                <span className="font-mono text-xs break-all">
                    {redirect.to_url}
                </span>
            ),
        },
        {
            key: 'status_code',
            label: 'Код',
            sortable: true,
            className: 'hidden sm:table-cell',
            render: (redirect) => (
                <Badge variant="outline">{redirect.status_code}</Badge>
            ),
        },
        {
            key: 'is_active',
            label: 'Статус',
            render: (redirect) =>
                redirect.is_active ? (
                    <Badge>Активен</Badge>
                ) : (
                    <Badge variant="secondary">Выключен</Badge>
                ),
        },
        {
            key: 'notes',
            label: 'Заметка',
            className:
                'hidden lg:table-cell text-muted-foreground max-w-xs truncate',
        },
    ];

    return (
        <>
            <Head title="Редиректы" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Редиректы</h1>
                    <p className="text-sm text-muted-foreground">
                        Постоянные и временные перенаправления со старых URL
                        портала (301/302). Записи из{' '}
                        <code className="text-xs">config/redirects.php</code>{' '}
                        по-прежнему учитываются при деплое.
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={redirects}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(redirect) => redirect.id}
                    searchPlaceholder="Поиск по пути, URL или заметке…"
                    actions={(redirect) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => {
                                    setEditing(redirect);
                                    setFormOpen(true);
                                }}
                            >
                                <Pencil className="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => setDeleting(redirect)}
                            >
                                <Trash2 className="size-4 text-destructive" />
                            </Button>
                        </div>
                    )}
                    toolbar={
                        <Button
                            type="button"
                            onClick={() => {
                                setEditing(null);
                                setFormOpen(true);
                            }}
                        >
                            <Plus className="mr-2 size-4" />
                            Добавить редирект
                        </Button>
                    }
                />
            </div>

            <RedirectFormDialog
                key={editing?.id ?? 'create'}
                open={formOpen}
                redirect={editing}
                onOpenChange={(open) => {
                    setFormOpen(open);

                    if (!open) {
                        setEditing(null);
                    }
                }}
            />

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Удалить редирект?</DialogTitle>
                        <DialogDescription>
                            Путь <code>/{deleting?.from_path}</code> перестанет
                            перенаправляться.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeleting(null)}
                        >
                            Отмена
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() => {
                                if (!deleting) {
                                    return;
                                }

                                router.delete(destroy(deleting.id).url, {
                                    preserveScroll: true,
                                    onSuccess: () => setDeleting(null),
                                });
                            }}
                        >
                            Удалить
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

type RedirectFormDialogProps = {
    open: boolean;
    redirect: RedirectRow | null;
    onOpenChange: (open: boolean) => void;
};

function RedirectFormDialog({
    open,
    redirect,
    onOpenChange,
}: RedirectFormDialogProps) {
    const isEdit = redirect !== null;

    const form = useForm({
        from_path: redirect?.from_path ?? '',
        to_url: redirect?.to_url ?? '',
        status_code: redirect?.status_code ?? 301,
        is_active: redirect?.is_active ?? true,
        notes: redirect?.notes ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEdit && redirect) {
            form.put(update(redirect.id).url, options);
        } else {
            form.post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {isEdit ? 'Изменить редирект' : 'Добавить редирект'}
                    </DialogTitle>
                    <DialogDescription>
                        Укажите старый путь без домена, например{' '}
                        <code>tj/node/123</code> или <code>ru/about-us</code>.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="from_path">Старый путь</Label>
                        <div className="flex items-center gap-2">
                            <span className="text-muted-foreground">/</span>
                            <Input
                                id="from_path"
                                placeholder="tj/node/123"
                                value={form.data.from_path}
                                onChange={(event) =>
                                    form.setData(
                                        'from_path',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <InputError message={form.errors.from_path} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="to_url">Новый URL</Label>
                        <Input
                            id="to_url"
                            placeholder="/tj/news/new-slug"
                            value={form.data.to_url}
                            onChange={(event) =>
                                form.setData('to_url', event.target.value)
                            }
                        />
                        <InputError message={form.errors.to_url} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="status_code">HTTP-код</Label>
                        <Select
                            value={String(form.data.status_code)}
                            onValueChange={(value) =>
                                form.setData('status_code', Number(value))
                            }
                        >
                            <SelectTrigger id="status_code">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="301">
                                    301 — постоянный
                                </SelectItem>
                                <SelectItem value="302">
                                    302 — временный
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.status_code} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="notes">Заметка (необязательно)</Label>
                        <Input
                            id="notes"
                            value={form.data.notes}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
                            }
                        />
                        <InputError message={form.errors.notes} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked === true)
                            }
                        />
                        <Label htmlFor="is_active">Активен</Label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            <ArrowRightLeft className="mr-2 size-4" />
                            {isEdit ? 'Сохранить' : 'Создать'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

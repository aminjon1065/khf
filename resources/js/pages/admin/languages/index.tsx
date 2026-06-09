import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState  } from 'react';
import type {FormEvent} from 'react';
import {
    DataTable
    
    
    
} from '@/components/admin/data-table';
import type {DataTableColumn, DataTableFilters, Paginator} from '@/components/admin/data-table';
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
import { dashboard } from '@/routes/admin';
import { destroy, index, store, update } from '@/routes/admin/languages';

type LanguageRow = {
    id: number;
    code: string;
    name: string;
    native_name: string;
    hreflang: string;
    direction: 'ltr' | 'rtl';
    is_active: boolean;
    is_default: boolean;
    sort_order: number;
};

type PageProps = {
    languages: Paginator<LanguageRow>;
    filters: DataTableFilters;
};

export default function LanguagesIndex({ languages, filters }: PageProps) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<LanguageRow | null>(null);
    const [deleting, setDeleting] = useState<LanguageRow | null>(null);

    const columns: DataTableColumn<LanguageRow>[] = [
        {
            key: 'code',
            label: 'Код',
            sortable: true,
            render: (language) => <span className="font-mono uppercase">{language.code}</span>,
        },
        { key: 'native_name', label: 'Название', sortable: true },
        { key: 'name', label: 'Англ. название', sortable: true, className: 'hidden md:table-cell' },
        { key: 'hreflang', label: 'hreflang', className: 'hidden md:table-cell text-muted-foreground' },
        {
            key: 'is_active',
            label: 'Статус',
            render: (language) =>
                language.is_active ? <Badge>Активен</Badge> : <Badge variant="secondary">Выключен</Badge>,
        },
        {
            key: 'is_default',
            label: 'По умолчанию',
            render: (language) => (language.is_default ? <Badge variant="outline">Да</Badge> : null),
        },
        { key: 'sort_order', label: 'Порядок', sortable: true, className: 'hidden sm:table-cell' },
    ];

    return (
        <>
            <Head title="Языки" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Языки</h1>
                    <p className="text-sm text-muted-foreground">Языки интерфейса и контента портала</p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={languages}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(language) => language.id}
                    searchPlaceholder="Поиск по названию или коду…"
                    toolbar={
                        <Button
                            onClick={() => {
                                setEditing(null);
                                setFormOpen(true);
                            }}
                        >
                            <Plus className="size-4" />
                            Добавить язык
                        </Button>
                    }
                    actions={(language) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                onClick={() => {
                                    setEditing(language);
                                    setFormOpen(true);
                                }}
                            >
                                <Pencil className="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                disabled={language.is_default}
                                onClick={() => setDeleting(language)}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    )}
                />
            </div>

            <LanguageFormDialog
                key={editing?.id ?? 'create'}
                open={formOpen}
                onOpenChange={setFormOpen}
                language={editing}
            />

            <DeleteLanguageDialog language={deleting} onClose={() => setDeleting(null)} />
        </>
    );
}

function LanguageFormDialog({
    open,
    onOpenChange,
    language,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    language: LanguageRow | null;
}) {
    const isEdit = Boolean(language);
    const form = useForm({
        code: language?.code ?? '',
        name: language?.name ?? '',
        native_name: language?.native_name ?? '',
        hreflang: language?.hreflang ?? '',
        direction: language?.direction ?? 'ltr',
        is_active: language?.is_active ?? true,
        is_default: language?.is_default ?? false,
        sort_order: language?.sort_order ?? 0,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: () => onOpenChange(false) };

        if (isEdit && language) {
            form.put(update(language.id).url, options);
        } else {
            form.post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{isEdit ? 'Изменить язык' : 'Добавить язык'}</DialogTitle>
                    <DialogDescription>Код используется в URL; hreflang — для SEO.</DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="code">Код</Label>
                            <Input
                                id="code"
                                value={form.data.code}
                                onChange={(event) => form.setData('code', event.target.value)}
                            />
                            <InputError message={form.errors.code} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="hreflang">hreflang</Label>
                            <Input
                                id="hreflang"
                                value={form.data.hreflang}
                                onChange={(event) => form.setData('hreflang', event.target.value)}
                            />
                            <InputError message={form.errors.hreflang} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="native_name">Название (на своём языке)</Label>
                        <Input
                            id="native_name"
                            value={form.data.native_name}
                            onChange={(event) => form.setData('native_name', event.target.value)}
                        />
                        <InputError message={form.errors.native_name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="name">Английское название</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="direction">Направление</Label>
                            <Select
                                value={form.data.direction}
                                onValueChange={(value) => form.setData('direction', value as 'ltr' | 'rtl')}
                            >
                                <SelectTrigger id="direction">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ltr">Слева направо (LTR)</SelectItem>
                                    <SelectItem value="rtl">Справа налево (RTL)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="sort_order">Порядок</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                min={0}
                                value={form.data.sort_order}
                                onChange={(event) => form.setData('sort_order', Number(event.target.value))}
                            />
                            <InputError message={form.errors.sort_order} />
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                        />
                        <Label htmlFor="is_active">Активен</Label>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_default"
                            checked={form.data.is_default}
                            onCheckedChange={(checked) => form.setData('is_default', checked === true)}
                        />
                        <Label htmlFor="is_default">Язык по умолчанию</Label>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Отмена
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {isEdit ? 'Сохранить' : 'Создать'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteLanguageDialog({
    language,
    onClose,
}: {
    language: LanguageRow | null;
    onClose: () => void;
}) {
    const submit = () => {
        if (!language) {
            return;
        }

        router.delete(destroy(language.id).url, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog open={Boolean(language)} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Удалить язык?</DialogTitle>
                    <DialogDescription>
                        Язык «{language?.native_name}» будет удалён. Это действие нельзя отменить.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Отмена
                    </Button>
                    <Button variant="destructive" onClick={submit}>
                        Удалить
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

LanguagesIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Языки', href: index() },
    ],
};

import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Ban, CircleCheck, Pencil, Plus, Trash2 } from 'lucide-react';
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
import { block, destroy, index, store, update } from '@/routes/admin/users';

type RoleOption = { value: string; label: string };

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    is_blocked: boolean;
    created_at: string | null;
};

type PageProps = {
    users: Paginator<UserRow>;
    filters: DataTableFilters;
    roles: RoleOption[];
};

export default function UsersIndex({ users, filters, roles }: PageProps) {
    const currentUserId = usePage().props.auth.user.id;
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [deleting, setDeleting] = useState<UserRow | null>(null);

    const roleLabel = (value: string | null) =>
        roles.find((role) => role.value === value)?.label ?? value ?? '—';

    const columns: DataTableColumn<UserRow>[] = [
        { key: 'name', label: 'Имя', sortable: true },
        { key: 'email', label: 'E-mail', sortable: true },
        {
            key: 'role',
            label: 'Роль',
            render: (user) => (
                <Badge variant="secondary">{roleLabel(user.role)}</Badge>
            ),
        },
        {
            key: 'is_blocked',
            label: 'Статус',
            render: (user) =>
                user.is_blocked ? (
                    <Badge variant="destructive">Заблокирован</Badge>
                ) : (
                    <Badge>Активен</Badge>
                ),
        },
        {
            key: 'created_at',
            label: 'Создан',
            sortable: true,
            className: 'hidden sm:table-cell',
        },
    ];

    return (
        <>
            <Head title="Пользователи" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Пользователи</h1>
                    <p className="text-sm text-muted-foreground">
                        Учётные записи сотрудников Комитета
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={users}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(user) => user.id}
                    searchPlaceholder="Поиск по имени или e-mail…"
                    toolbar={
                        <Button
                            onClick={() => {
                                setEditing(null);
                                setFormOpen(true);
                            }}
                        >
                            <Plus className="size-4" />
                            Добавить пользователя
                        </Button>
                    }
                    actions={(user) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                onClick={() => {
                                    setEditing(user);
                                    setFormOpen(true);
                                }}
                            >
                                <Pencil className="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={
                                    user.is_blocked
                                        ? 'Разблокировать'
                                        : 'Заблокировать'
                                }
                                disabled={user.id === currentUserId}
                                onClick={() =>
                                    router.patch(
                                        block(user.id).url,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {user.is_blocked ? (
                                    <CircleCheck className="size-4" />
                                ) : (
                                    <Ban className="size-4" />
                                )}
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                disabled={user.id === currentUserId}
                                onClick={() => setDeleting(user)}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    )}
                />
            </div>

            <UserFormDialog
                key={editing?.id ?? 'create'}
                open={formOpen}
                onOpenChange={setFormOpen}
                user={editing}
                roles={roles}
            />

            <DeleteUserDialog
                user={deleting}
                onClose={() => setDeleting(null)}
            />
        </>
    );
}

function UserFormDialog({
    open,
    onOpenChange,
    user,
    roles,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    user: UserRow | null;
    roles: RoleOption[];
}) {
    const isEdit = Boolean(user);
    const form = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        role: user?.role ?? roles[roles.length - 1]?.value ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEdit && user) {
            form.put(update(user.id).url, options);
        } else {
            form.post(store().url, options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {isEdit
                            ? 'Изменить пользователя'
                            : 'Добавить пользователя'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEdit
                            ? 'Оставьте пароль пустым, чтобы не менять его.'
                            : 'Новому сотруднику потребуется включить 2FA при первом входе.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">Имя</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">E-mail</Label>
                        <Input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password">
                            {isEdit ? 'Новый пароль' : 'Пароль'}
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                        />
                        <InputError message={form.errors.password} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="role">Роль</Label>
                        <Select
                            value={form.data.role}
                            onValueChange={(value) =>
                                form.setData('role', value)
                            }
                        >
                            <SelectTrigger id="role">
                                <SelectValue placeholder="Выберите роль" />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem
                                        key={role.value}
                                        value={role.value}
                                    >
                                        {role.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.role} />
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
                            {isEdit ? 'Сохранить' : 'Создать'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteUserDialog({
    user,
    onClose,
}: {
    user: UserRow | null;
    onClose: () => void;
}) {
    const submit = () => {
        if (!user) {
            return;
        }

        router.delete(destroy(user.id).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog
            open={Boolean(user)}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Удалить пользователя?</DialogTitle>
                    <DialogDescription>
                        Учётная запись «{user?.name}» будет удалена. Это
                        действие нельзя отменить.
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

UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Пользователи', href: index() },
    ],
};

import { Head, Link, router } from '@inertiajs/react';
import { ArchiveRestore, ArrowLeft, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { Paginator } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes/admin';
import { forceDelete, index, restore, trash } from '@/routes/admin/alerts';

type AlertRow = {
    id: number;
    title: string;
    hazard_label: string;
    deleted_at: string | null;
};

type PageProps = {
    alerts: Paginator<AlertRow>;
};

export default function AlertsTrash({ alerts }: PageProps) {
    const [purging, setPurging] = useState<AlertRow | null>(null);

    return (
        <>
            <Head title="Корзина — оповещения" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Корзина</h1>
                        <p className="text-sm text-muted-foreground">
                            Удалённые оповещения можно восстановить
                        </p>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="size-4" />К оповещениям
                        </Link>
                    </Button>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Оповещение</TableHead>
                                <TableHead>Уровень</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Удалено
                                </TableHead>
                                <TableHead className="w-0 text-right">
                                    Действия
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {alerts.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Корзина пуста
                                    </TableCell>
                                </TableRow>
                            ) : (
                                alerts.data.map((alert) => (
                                    <TableRow key={alert.id}>
                                        <TableCell>{alert.title}</TableCell>
                                        <TableCell>
                                            {alert.hazard_label}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {alert.deleted_at}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Восстановить"
                                                    onClick={() =>
                                                        router.patch(
                                                            restore(alert.id)
                                                                .url,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <ArchiveRestore className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Удалить навсегда"
                                                    onClick={() =>
                                                        setPurging(alert)
                                                    }
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog
                open={Boolean(purging)}
                onOpenChange={(open) => !open && setPurging(null)}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Удалить навсегда?</DialogTitle>
                        <DialogDescription>
                            «{purging?.title}» будет удалено безвозвратно.
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
                                if (purging) {
                                    router.delete(forceDelete(purging.id).url, {
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
        </>
    );
}

AlertsTrash.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Оповещения', href: index() },
        { title: 'Корзина', href: trash() },
    ],
};

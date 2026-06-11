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
import { forceDelete, index, restore, trash } from '@/routes/admin/incidents';

type IncidentRow = {
    id: number;
    title: string;
    type_label: string;
    status_label: string;
    deleted_at: string | null;
};

type PageProps = {
    incidents: Paginator<IncidentRow>;
};

export default function IncidentsTrash({ incidents }: PageProps) {
    const [purging, setPurging] = useState<IncidentRow | null>(null);

    return (
        <>
            <Head title="Корзина — события ЧС" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Корзина</h1>
                        <p className="text-sm text-muted-foreground">
                            Удалённые события можно восстановить
                        </p>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="size-4" />К событиям
                        </Link>
                    </Button>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Событие</TableHead>
                                <TableHead>Тип</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Удалено
                                </TableHead>
                                <TableHead className="w-0 text-right">
                                    Действия
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {incidents.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Корзина пуста
                                    </TableCell>
                                </TableRow>
                            ) : (
                                incidents.data.map((incident) => (
                                    <TableRow key={incident.id}>
                                        <TableCell>{incident.title}</TableCell>
                                        <TableCell>
                                            {incident.type_label}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {incident.deleted_at}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Восстановить"
                                                    onClick={() =>
                                                        router.patch(
                                                            restore(incident.id)
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
                                                        setPurging(incident)
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

IncidentsTrash.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'События ЧС', href: index() },
        { title: 'Корзина', href: trash() },
    ],
};

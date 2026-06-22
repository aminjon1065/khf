import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';
import type { FormEvent } from 'react';
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
import { index, update } from '@/routes/admin/appeals';

type Option = { value: string; label: string };
type Staff = { id: number; name: string };
type Attachment = { id: number; name: string; url: string; size: string };

type Appeal = {
    id: number;
    reference: string;
    category_label: string;
    name: string;
    email: string;
    phone: string | null;
    subject: string;
    message: string;
    status: string;
    assigned_to: number | null;
    internal_note: string | null;
    deadline_at: string | null;
    created_at: string | null;
    attachments: Attachment[];
};

type PageProps = {
    appeal: Appeal;
    statuses: Option[];
    staff: Staff[];
};

export default function AppealShow({ appeal, statuses, staff }: PageProps) {
    const form = useForm({
        status: appeal.status,
        assigned_to: appeal.assigned_to,
        internal_note: appeal.internal_note ?? '',
        deadline_at: appeal.deadline_at ?? '',
    });

    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update(appeal.id).url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={`Обращение ${appeal.reference}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="mr-2 size-4" />К обращениям
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
                    <div className="space-y-4 rounded-lg border p-5">
                        <div className="flex items-center justify-between">
                            <h1 className="text-xl font-semibold">
                                {appeal.subject}
                            </h1>
                            <span className="font-mono text-sm text-muted-foreground">
                                {appeal.reference}
                            </span>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {appeal.category_label} · {appeal.created_at}
                        </p>
                        <dl className="grid gap-2 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground">Имя</dt>
                                <dd>{appeal.name}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    E-mail
                                </dt>
                                <dd>{appeal.email}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Телефон
                                </dt>
                                <dd>{appeal.phone ?? '—'}</dd>
                            </div>
                        </dl>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Сообщение
                            </p>
                            <p className="mt-1 whitespace-pre-line">
                                {appeal.message}
                            </p>
                        </div>

                        {appeal.attachments.length > 0 && (
                            <div className="mt-6 border-t pt-4">
                                <h3 className="mb-3 text-sm font-medium text-muted-foreground">
                                    Вложения
                                </h3>
                                <ul className="space-y-2">
                                    {appeal.attachments.map((attachment) => (
                                        <li
                                            key={attachment.id}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <a
                                                href={attachment.url}
                                                target="_blank"
                                                className="flex items-center gap-2 text-primary hover:underline"
                                                download
                                            >
                                                <Download className="size-4" />
                                                <span>{attachment.name}</span>
                                            </a>
                                            <span className="text-muted-foreground">
                                                ({attachment.size})
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>

                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg border p-5"
                    >
                        <h2 className="font-semibold">Обработка</h2>
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
                            <Label htmlFor="assigned_to">Ответственный</Label>
                            <Select
                                value={
                                    form.data.assigned_to
                                        ? String(form.data.assigned_to)
                                        : 'none'
                                }
                                onValueChange={(value) =>
                                    form.setData(
                                        'assigned_to',
                                        value === 'none' ? null : Number(value),
                                    )
                                }
                            >
                                <SelectTrigger id="assigned_to">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Не назначен
                                    </SelectItem>
                                    {staff.map((member) => (
                                        <SelectItem
                                            key={member.id}
                                            value={String(member.id)}
                                        >
                                            {member.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.assigned_to} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="deadline_at">Срок исполнения</Label>
                            <Input
                                id="deadline_at"
                                type="date"
                                value={form.data.deadline_at}
                                onChange={(e) =>
                                    form.setData('deadline_at', e.target.value)
                                }
                            />
                            <InputError message={errors.deadline_at} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="internal_note">
                                Внутренний комментарий
                            </Label>
                            <Textarea
                                id="internal_note"
                                rows={5}
                                value={form.data.internal_note}
                                onChange={(event) =>
                                    form.setData(
                                        'internal_note',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.internal_note} />
                        </div>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="w-full"
                        >
                            Сохранить
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}

AppealShow.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Обращения', href: index() },
    ],
};

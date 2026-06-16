import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { index, update } from '@/routes/admin/vacancy-applications';

type Option = { value: string; label: string };
type Staff = { id: number; name: string };

type Application = {
    id: number;
    reference: string;
    vacancy: string | null;
    full_name: string;
    email: string;
    phone: string | null;
    cover_letter: string | null;
    status: string;
    assigned_to: number | null;
    internal_note: string | null;
    created_at: string | null;
    resume: { name: string; size: string; url: string } | null;
};

type PageProps = {
    application: Application;
    statuses: Option[];
    staff: Staff[];
};

export default function VacancyApplicationShow({
    application,
    statuses,
    staff,
}: PageProps) {
    const form = useForm({
        status: application.status,
        assigned_to: application.assigned_to,
        internal_note: application.internal_note ?? '',
    });

    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update(application.id).url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={`Заявка ${application.reference}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="size-4" />К заявкам
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
                    <div className="space-y-4 rounded-lg border p-5">
                        <div className="flex items-center justify-between">
                            <h1 className="text-xl font-semibold">
                                {application.full_name}
                            </h1>
                            <span className="font-mono text-sm text-muted-foreground">
                                {application.reference}
                            </span>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {application.vacancy ?? '—'} ·{' '}
                            {application.created_at}
                        </p>
                        <dl className="grid gap-2 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground">
                                    E-mail
                                </dt>
                                <dd>{application.email}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Телефон
                                </dt>
                                <dd>{application.phone ?? '—'}</dd>
                            </div>
                        </dl>
                        {application.cover_letter && (
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Сопроводительное письмо
                                </p>
                                <p className="mt-1 whitespace-pre-line">
                                    {application.cover_letter}
                                </p>
                            </div>
                        )}
                        {application.resume && (
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Анкета / резюме
                                </p>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="mt-1"
                                    asChild
                                >
                                    <a
                                        href={application.resume.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Download className="size-4" />
                                        {application.resume.name} (
                                        {application.resume.size})
                                    </a>
                                </Button>
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

VacancyApplicationShow.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Заявки на вакансии', href: index() },
    ],
};

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
import { index, update } from '@/routes/admin/tender-bids';

type Option = { value: string; label: string };
type Staff = { id: number; name: string };

type Bid = {
    id: number;
    reference: string;
    tender: string | null;
    company_name: string;
    contact_name: string;
    email: string;
    phone: string | null;
    proposal: string | null;
    status: string;
    assigned_to: number | null;
    internal_note: string | null;
    created_at: string | null;
    document: { name: string; size: string; url: string } | null;
};

type PageProps = {
    bid: Bid;
    statuses: Option[];
    staff: Staff[];
};

export default function TenderBidShow({ bid, statuses, staff }: PageProps) {
    const form = useForm({
        status: bid.status,
        assigned_to: bid.assigned_to,
        internal_note: bid.internal_note ?? '',
    });

    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update(bid.id).url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={`Заявка ${bid.reference}`} />

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
                                {bid.company_name}
                            </h1>
                            <span className="font-mono text-sm text-muted-foreground">
                                {bid.reference}
                            </span>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {bid.tender ?? '—'} · {bid.created_at}
                        </p>
                        <dl className="grid gap-2 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground">
                                    Контактное лицо
                                </dt>
                                <dd>{bid.contact_name}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    E-mail
                                </dt>
                                <dd>{bid.email}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Телефон
                                </dt>
                                <dd>{bid.phone ?? '—'}</dd>
                            </div>
                        </dl>
                        {bid.proposal && (
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Комментарий к заявке
                                </p>
                                <p className="mt-1 whitespace-pre-line">
                                    {bid.proposal}
                                </p>
                            </div>
                        )}
                        {bid.document && (
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Документы заявки
                                </p>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="mt-1"
                                    asChild
                                >
                                    <a
                                        href={bid.document.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Download className="size-4" />
                                        {bid.document.name} ({bid.document.size}
                                        )
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

TenderBidShow.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Заявки на тендеры', href: index() },
    ],
};

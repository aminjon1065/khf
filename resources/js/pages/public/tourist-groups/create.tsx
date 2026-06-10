import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import type {FormEvent} from 'react';
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
import { create, store, track } from '@/routes/tourist-groups';

type RegionOption = { id: number; name: string };

type PageProps = {
    regions: RegionOption[];
    submittedReference: string | null;
};

export default function TouristGroupCreate({ regions, submittedReference }: PageProps) {
    const { locale } = usePage().props;

    const form = useForm({
        leader_name: '',
        leader_phone: '',
        leader_email: '',
        participants_count: 1,
        route: '',
        equipment: '',
        start_date: '',
        end_date: '',
        region_id: null as number | null,
        website: '',
    });

    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store({ locale }).url, { preserveScroll: true });
    };

    if (submittedReference) {
        return (
            <>
                <Head title="Заявка принята" />
                <div className="mx-auto max-w-xl rounded-lg border p-8 text-center">
                    <CheckCircle2 className="mx-auto size-12 text-green-600" />
                    <h1 className="mt-4 text-2xl font-semibold">Заявка зарегистрирована</h1>
                    <p className="mt-2 text-muted-foreground">Регистрационный номер для отслеживания:</p>
                    <p className="mt-2 font-mono text-xl font-semibold">{submittedReference}</p>
                    <div className="mt-6 flex justify-center gap-3">
                        <Button variant="outline" asChild>
                            <Link href={track({ locale }).url}>Отследить статус</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create({ locale }).url}>Новая заявка</Link>
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Регистрация туристской группы" />

            <div className="mx-auto max-w-2xl">
                <h1 className="text-3xl font-semibold">Регистрация туристской группы</h1>
                <p className="mt-1 text-muted-foreground">
                    Уведомите горноспасательную службу о маршруте для вашей безопасности
                </p>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="leader_name">Руководитель группы</Label>
                            <Input id="leader_name" value={form.data.leader_name} onChange={(e) => form.setData('leader_name', e.target.value)} />
                            <InputError message={errors.leader_name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="leader_phone">Телефон</Label>
                            <Input id="leader_phone" value={form.data.leader_phone} onChange={(e) => form.setData('leader_phone', e.target.value)} />
                            <InputError message={errors.leader_phone} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="leader_email">E-mail (необязательно)</Label>
                            <Input id="leader_email" type="email" value={form.data.leader_email} onChange={(e) => form.setData('leader_email', e.target.value)} />
                            <InputError message={errors.leader_email} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="participants_count">Число участников</Label>
                            <Input
                                id="participants_count"
                                type="number"
                                min={1}
                                value={form.data.participants_count}
                                onChange={(e) => form.setData('participants_count', Number(e.target.value))}
                            />
                            <InputError message={errors.participants_count} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="region">Регион</Label>
                        <Select
                            value={form.data.region_id ? String(form.data.region_id) : 'none'}
                            onValueChange={(value) => form.setData('region_id', value === 'none' ? null : Number(value))}
                        >
                            <SelectTrigger id="region">
                                <SelectValue placeholder="Выберите регион" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Не указан</SelectItem>
                                {regions.map((region) => (
                                    <SelectItem key={region.id} value={String(region.id)}>
                                        {region.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.region_id} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="start_date">Дата выхода</Label>
                            <Input id="start_date" type="date" value={form.data.start_date} onChange={(e) => form.setData('start_date', e.target.value)} />
                            <InputError message={errors.start_date} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="end_date">Дата возвращения</Label>
                            <Input id="end_date" type="date" value={form.data.end_date} onChange={(e) => form.setData('end_date', e.target.value)} />
                            <InputError message={errors.end_date} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="route">Маршрут</Label>
                        <Textarea id="route" rows={4} value={form.data.route} onChange={(e) => form.setData('route', e.target.value)} />
                        <InputError message={errors.route} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="equipment">Снаряжение и особенности (необязательно)</Label>
                        <Textarea id="equipment" rows={3} value={form.data.equipment} onChange={(e) => form.setData('equipment', e.target.value)} />
                        <InputError message={errors.equipment} />
                    </div>

                    {/* Honeypot (ТЗ §12.4). */}
                    <input
                        type="text"
                        tabIndex={-1}
                        autoComplete="off"
                        aria-hidden="true"
                        className="hidden"
                        value={form.data.website}
                        onChange={(e) => form.setData('website', e.target.value)}
                    />

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            Зарегистрировать группу
                        </Button>
                        <Link href={track({ locale }).url} className="text-sm text-primary hover:underline">
                            Отследить заявку
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
}

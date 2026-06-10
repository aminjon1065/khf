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
import { useTranslations } from '@/hooks/use-translations';
import { create, store, track } from '@/routes/tourist-groups';

type RegionOption = { id: number; name: string };

type PageProps = {
    regions: RegionOption[];
    submittedReference: string | null;
};

export default function TouristGroupCreate({ regions, submittedReference }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

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
                <Head title={t('tourism.create.success_page_title')} />
                <div className="mx-auto max-w-xl rounded-lg border p-8 text-center">
                    <CheckCircle2 className="mx-auto size-12 text-green-600" />
                    <h1 className="mt-4 text-2xl font-semibold">{t('tourism.create.success_heading')}</h1>
                    <p className="mt-2 text-muted-foreground">{t('tourism.create.reference_hint')}</p>
                    <p className="mt-2 font-mono text-xl font-semibold">{submittedReference}</p>
                    <div className="mt-6 flex justify-center gap-3">
                        <Button variant="outline" asChild>
                            <Link href={track({ locale }).url}>{t('common.track_status')}</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create({ locale }).url}>{t('tourism.create.new_application_button')}</Link>
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={t('tourism.create.page_title')} />

            <div className="mx-auto max-w-2xl">
                <h1 className="text-3xl font-semibold">{t('tourism.create.page_title')}</h1>
                <p className="mt-1 text-muted-foreground">
                    {t('tourism.create.subtitle')}
                </p>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="leader_name">{t('tourism.form.leader_name')}</Label>
                            <Input id="leader_name" value={form.data.leader_name} onChange={(e) => form.setData('leader_name', e.target.value)} />
                            <InputError message={errors.leader_name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="leader_phone">{t('tourism.form.leader_phone')}</Label>
                            <Input id="leader_phone" value={form.data.leader_phone} onChange={(e) => form.setData('leader_phone', e.target.value)} />
                            <InputError message={errors.leader_phone} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="leader_email">{t('tourism.form.leader_email')}</Label>
                            <Input id="leader_email" type="email" value={form.data.leader_email} onChange={(e) => form.setData('leader_email', e.target.value)} />
                            <InputError message={errors.leader_email} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="participants_count">{t('tourism.form.participants_count')}</Label>
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
                        <Label htmlFor="region">{t('tourism.form.region')}</Label>
                        <Select
                            value={form.data.region_id ? String(form.data.region_id) : 'none'}
                            onValueChange={(value) => form.setData('region_id', value === 'none' ? null : Number(value))}
                        >
                            <SelectTrigger id="region">
                                <SelectValue placeholder={t('tourism.form.region_placeholder')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">{t('tourism.form.region_none')}</SelectItem>
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
                            <Label htmlFor="start_date">{t('tourism.form.start_date')}</Label>
                            <Input id="start_date" type="date" value={form.data.start_date} onChange={(e) => form.setData('start_date', e.target.value)} />
                            <InputError message={errors.start_date} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="end_date">{t('tourism.form.end_date')}</Label>
                            <Input id="end_date" type="date" value={form.data.end_date} onChange={(e) => form.setData('end_date', e.target.value)} />
                            <InputError message={errors.end_date} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="route">{t('tourism.form.route')}</Label>
                        <Textarea id="route" rows={4} value={form.data.route} onChange={(e) => form.setData('route', e.target.value)} />
                        <InputError message={errors.route} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="equipment">{t('tourism.form.equipment')}</Label>
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
                            {t('tourism.form.submit')}
                        </Button>
                        <Link href={track({ locale }).url} className="text-sm text-primary hover:underline">
                            {t('tourism.create.track_link')}
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
}

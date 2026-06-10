import { Head, useForm, usePage } from '@inertiajs/react';
import type {FormEvent} from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { store } from '@/routes/subscriptions';

type Option = { value: string; label: string };
type RegionOption = { id: number; name: string };

type PageProps = {
    topics: Option[];
    regions: RegionOption[];
    status: 'pending' | 'confirmed' | 'unsubscribed' | 'invalid' | null;
};

const statusMessages: Record<string, { title: string; tone: 'success' | 'info' | 'error' }> = {
    pending: { title: 'Проверьте почту и подтвердите подписку.', tone: 'info' },
    confirmed: { title: 'Подписка подтверждена. Спасибо!', tone: 'success' },
    unsubscribed: { title: 'Вы отписались от уведомлений.', tone: 'info' },
    invalid: { title: 'Ссылка недействительна или устарела.', tone: 'error' },
};

export default function Subscribe({ topics, regions, status }: PageProps) {
    const { locale } = usePage().props;

    const form = useForm({
        email: '',
        topics: [topics[0]?.value].filter(Boolean) as string[],
        region_id: null as number | null,
        consent: false,
        website: '',
    });

    const errors = form.errors as Record<string, string>;

    const toggleTopic = (value: string, checked: boolean) => {
        form.setData('topics', checked ? [...form.data.topics, value] : form.data.topics.filter((t) => t !== value));
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store({ locale }).url, { preserveScroll: true });
    };

    const banner = status ? statusMessages[status] : null;

    return (
        <>
            <Head title="Подписка на уведомления" />

            <div className="mx-auto max-w-xl">
                <h1 className="text-3xl font-semibold">Подписка на уведомления</h1>
                <p className="mt-1 text-muted-foreground">
                    Получайте оповещения о ЧС и новости на электронную почту
                </p>

                {banner && (
                    <p
                        className={
                            'mt-6 rounded-md border p-4 text-sm ' +
                            (banner.tone === 'success'
                                ? 'border-green-600/40 bg-green-600/5'
                                : banner.tone === 'error'
                                  ? 'border-destructive/40 bg-destructive/5'
                                  : 'border-primary/40 bg-primary/5')
                        }
                    >
                        {banner.title}
                    </p>
                )}

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="email">E-mail</Label>
                        <Input id="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                        <InputError message={errors.email} />
                    </div>

                    <div className="space-y-2">
                        <Label>Темы</Label>
                        <div className="space-y-2">
                            {topics.map((topic) => (
                                <label key={topic.value} className="flex items-center gap-2 text-sm">
                                    <Checkbox
                                        checked={form.data.topics.includes(topic.value)}
                                        onCheckedChange={(checked) => toggleTopic(topic.value, checked === true)}
                                    />
                                    {topic.label}
                                </label>
                            ))}
                        </div>
                        <InputError message={errors.topics} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="region">Регион (необязательно)</Label>
                        <Select
                            value={form.data.region_id ? String(form.data.region_id) : 'none'}
                            onValueChange={(value) => form.setData('region_id', value === 'none' ? null : Number(value))}
                        >
                            <SelectTrigger id="region">
                                <SelectValue placeholder="Все регионы" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Все регионы</SelectItem>
                                {regions.map((region) => (
                                    <SelectItem key={region.id} value={String(region.id)}>
                                        {region.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <label className="flex items-start gap-2 text-sm">
                        <Checkbox
                            checked={form.data.consent}
                            onCheckedChange={(checked) => form.setData('consent', checked === true)}
                        />
                        <span>Я согласен на обработку персональных данных и получение рассылки.</span>
                    </label>
                    <InputError message={errors.consent} />

                    <input
                        type="text"
                        tabIndex={-1}
                        autoComplete="off"
                        aria-hidden="true"
                        className="hidden"
                        value={form.data.website}
                        onChange={(e) => form.setData('website', e.target.value)}
                    />

                    <Button type="submit" disabled={form.processing}>
                        Подписаться
                    </Button>
                </form>
            </div>
        </>
    );
}

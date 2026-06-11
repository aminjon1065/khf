import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
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
import { useTranslations } from '@/hooks/use-translations';
import { store } from '@/routes/subscriptions';

type Option = { value: string; label: string };
type RegionOption = { id: number; name: string };

type PageProps = {
    topics: Option[];
    regions: RegionOption[];
    status: 'pending' | 'confirmed' | 'unsubscribed' | 'invalid' | null;
};

export default function Subscribe({ topics, regions, status }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    const statusMessages: Record<
        string,
        { title: string; tone: 'success' | 'info' | 'error' }
    > = {
        pending: { title: t('subscribe.status.pending'), tone: 'info' },
        confirmed: { title: t('subscribe.status.confirmed'), tone: 'success' },
        unsubscribed: {
            title: t('subscribe.status.unsubscribed'),
            tone: 'info',
        },
        invalid: { title: t('subscribe.status.invalid'), tone: 'error' },
    };

    const form = useForm({
        email: '',
        topics: [topics[0]?.value].filter(Boolean) as string[],
        region_id: null as number | null,
        consent: false,
        website: '',
    });

    const errors = form.errors as Record<string, string>;

    const toggleTopic = (value: string, checked: boolean) => {
        form.setData(
            'topics',
            checked
                ? [...form.data.topics, value]
                : form.data.topics.filter((t) => t !== value),
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store({ locale }).url, { preserveScroll: true });
    };

    const banner = status ? statusMessages[status] : null;

    return (
        <>
            <Head title={t('subscribe.title')} />

            <div className="mx-auto max-w-xl">
                <h1 className="text-3xl font-semibold">
                    {t('subscribe.title')}
                </h1>
                <p className="mt-1 text-muted-foreground">
                    {t('subscribe.subtitle')}
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
                        <Label htmlFor="email">{t('common.email')}</Label>
                        <Input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(e) =>
                                form.setData('email', e.target.value)
                            }
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="space-y-2">
                        <Label>{t('subscribe.form.topics')}</Label>
                        <div className="space-y-2">
                            {topics.map((topic) => (
                                <label
                                    key={topic.value}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        checked={form.data.topics.includes(
                                            topic.value,
                                        )}
                                        onCheckedChange={(checked) =>
                                            toggleTopic(
                                                topic.value,
                                                checked === true,
                                            )
                                        }
                                    />
                                    {topic.label}
                                </label>
                            ))}
                        </div>
                        <InputError message={errors.topics} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="region">
                            {t('subscribe.form.region_optional')}
                        </Label>
                        <Select
                            value={
                                form.data.region_id
                                    ? String(form.data.region_id)
                                    : 'none'
                            }
                            onValueChange={(value) =>
                                form.setData(
                                    'region_id',
                                    value === 'none' ? null : Number(value),
                                )
                            }
                        >
                            <SelectTrigger id="region">
                                <SelectValue
                                    placeholder={t(
                                        'subscribe.form.all_regions',
                                    )}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    {t('subscribe.form.all_regions')}
                                </SelectItem>
                                {regions.map((region) => (
                                    <SelectItem
                                        key={region.id}
                                        value={String(region.id)}
                                    >
                                        {region.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <label className="flex items-start gap-2 text-sm">
                        <Checkbox
                            checked={form.data.consent}
                            onCheckedChange={(checked) =>
                                form.setData('consent', checked === true)
                            }
                        />
                        <span>{t('subscribe.form.consent')}</span>
                    </label>
                    <InputError message={errors.consent} />

                    <input
                        type="text"
                        tabIndex={-1}
                        autoComplete="off"
                        aria-hidden="true"
                        className="hidden"
                        value={form.data.website}
                        onChange={(e) =>
                            form.setData('website', e.target.value)
                        }
                    />

                    <Button type="submit" disabled={form.processing}>
                        {t('subscribe.form.submit')}
                    </Button>
                </form>
            </div>
        </>
    );
}

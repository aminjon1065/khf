import { Head, useForm, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useEffect, useState } from 'react';
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
import { useMatomoGoal } from '@/hooks/use-matomo-goal';
import { useTranslations } from '@/hooks/use-translations';
import { subscribe as pushSubscribeRoute } from '@/routes/push';
import { store } from '@/routes/subscriptions';

type Option = { value: string; label: string };
type RegionOption = { id: number; name: string };

type PageProps = {
    topics: Option[];
    regions: RegionOption[];
    status: 'pending' | 'confirmed' | 'unsubscribed' | 'invalid' | null;
    vapidPublicKey?: string;
};

// Base64 helper for VAPID key
function urlBase64ToUint8Array(base64String: string) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

export default function Subscribe({
    topics,
    regions,
    status,
    vapidPublicKey,
}: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();
    const [isPushSupported, setIsPushSupported] = useState(false);
    const [pushStatus, setPushStatus] = useState<
        'idle' | 'loading' | 'success' | 'error'
    >('idle');

    useEffect(() => {
        // Feature detection must run client-side after hydration; a lazy initializer would read
        // `navigator` on the server and cause a hydration mismatch.
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            // eslint-disable-next-line react-hooks/set-state-in-effect -- intentional client-only feature detection
            setIsPushSupported(true);
            navigator.serviceWorker.register('/sw.js');
        }
    }, []);

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
        push_success: { title: t('subscribe.push.success'), tone: 'success' },
        push_error: { title: t('subscribe.push.error'), tone: 'error' },
    };

    const form = useForm({
        email: '',
        topics: [topics[0]?.value].filter(Boolean) as string[],
        region_id: null as number | null,
        consent: false,
        website: '',
    });

    const errors = form.errors as Record<string, string>;

    useMatomoGoal('subscription', status === 'pending');

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

    const handlePushSubscribe = async () => {
        if (!vapidPublicKey) {
            console.error('VAPID public key not found');

            return;
        }

        try {
            setPushStatus('loading');
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                throw new Error('Permission denied');
            }

            const registration = await navigator.serviceWorker.ready;
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                });
            }

            let token = localStorage.getItem('push_subscriber_token');

            if (!token) {
                token = crypto.randomUUID();
                localStorage.setItem('push_subscriber_token', token);
            }

            const key = subscription.getKey
                ? subscription.getKey('p256dh')
                : '';
            const auth = subscription.getKey ? subscription.getKey('auth') : '';

            const response = await fetch(pushSubscribeRoute({ locale }).url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        (
                            document.head.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement
                        )?.content || '',
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: key
                            ? btoa(
                                  String.fromCharCode.apply(
                                      null,
                                      new Uint8Array(
                                          key,
                                      ) as unknown as number[],
                                  ),
                              )
                            : '',
                        auth: auth
                            ? btoa(
                                  String.fromCharCode.apply(
                                      null,
                                      new Uint8Array(
                                          auth,
                                      ) as unknown as number[],
                                  ),
                              )
                            : '',
                    },
                    subscriber_token: token,
                    topics: form.data.topics,
                    region_id: form.data.region_id,
                    locale: locale,
                }),
            });

            if (!response.ok) {
                throw new Error('Failed to save subscription on server');
            }

            setPushStatus('success');
        } catch (error) {
            console.error(error);
            setPushStatus('error');
        }
    };

    const currentStatus =
        pushStatus === 'success'
            ? 'push_success'
            : pushStatus === 'error'
              ? 'push_error'
              : status;
    const banner = currentStatus ? statusMessages[currentStatus] : null;

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
                        role={banner.tone === 'error' ? 'alert' : 'status'}
                        aria-live="polite"
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

                <form onSubmit={submit} className="mt-6 space-y-8">
                    {/* Preferences Selection */}
                    <div className="space-y-4 rounded-lg border p-4">
                        <h2 className="text-lg font-medium">
                            {t('subscribe.sections.preferences')}
                        </h2>
                        <fieldset
                            className="space-y-2"
                            aria-describedby={
                                errors.topics ? 'topics-error' : undefined
                            }
                        >
                            <legend className="text-sm font-medium">
                                {t('subscribe.form.topics')}
                            </legend>
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
                            <InputError
                                id="topics-error"
                                message={errors.topics}
                            />
                        </fieldset>

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
                    </div>

                    {/* Channel Selection */}
                    <div className="space-y-4 rounded-lg border p-4">
                        <h2 className="text-lg font-medium">
                            {t('subscribe.sections.channels')}
                        </h2>

                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Email Subscription */}
                            <div className="space-y-4 border-l-2 pl-4">
                                <h3 className="font-medium">
                                    {t('subscribe.email_channel')}
                                </h3>
                                <div className="space-y-2">
                                    <Label htmlFor="email">
                                        {t('common.email')}
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(e) =>
                                            form.setData(
                                                'email',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="your@email.com"
                                        aria-invalid={!!errors.email}
                                        aria-describedby={
                                            errors.email
                                                ? 'email-error'
                                                : undefined
                                        }
                                    />
                                    <InputError
                                        id="email-error"
                                        message={errors.email}
                                    />
                                </div>
                                <label className="flex items-start gap-2 text-sm">
                                    <Checkbox
                                        checked={form.data.consent}
                                        onCheckedChange={(checked) =>
                                            form.setData(
                                                'consent',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <span>{t('subscribe.form.consent')}</span>
                                </label>
                                <InputError message={errors.consent} />
                                <Button
                                    type="submit"
                                    disabled={
                                        form.processing ||
                                        !form.data.email ||
                                        !form.data.consent
                                    }
                                >
                                    {t('subscribe.form.submit')}
                                </Button>
                            </div>

                            {/* Push Subscription */}
                            <div className="space-y-4 border-l-2 pl-4">
                                <h3 className="font-medium">
                                    {t('subscribe.push.channel')}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {t('subscribe.push.hint')}
                                </p>
                                {!isPushSupported ? (
                                    <p className="text-sm text-orange-600">
                                        {t('subscribe.push.unsupported')}
                                    </p>
                                ) : pushStatus === 'success' ? (
                                    <p className="inline-flex items-center gap-2 text-sm font-medium text-green-600">
                                        <Check
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                        {t('subscribe.push.enabled')}
                                    </p>
                                ) : (
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={handlePushSubscribe}
                                        disabled={pushStatus === 'loading'}
                                    >
                                        {pushStatus === 'loading'
                                            ? t('subscribe.push.loading')
                                            : t('subscribe.push.enable')}
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>

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
                </form>
            </div>
        </>
    );
}

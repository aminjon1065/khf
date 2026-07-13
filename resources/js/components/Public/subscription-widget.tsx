import { useForm } from '@inertiajs/react';
import { Bell, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/hooks/use-translations';
import { store as subscriptionsStore } from '@/routes/subscriptions';

export function SubscriptionWidget({ locale }: { locale: string }) {
    const { t } = useTranslations();
    const { data, setData, post, processing, errors, recentlySuccessful } =
        useForm({
            email: '',
            locale: locale,
        });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(subscriptionsStore({ locale }).url, {
            preserveScroll: true,
            onSuccess: () => setData('email', ''),
        });
    };

    return (
        <div className="flex flex-col overflow-hidden rounded-2xl border bg-card p-6 shadow-sm transition-all duration-300 hover:shadow-md">
            <div className="mb-4 flex items-center gap-3">
                <span className="flex size-10 items-center justify-center rounded-xl bg-signal/10 text-signal">
                    <Bell className="size-5" />
                </span>
                <div>
                    <h3 className="font-semibold text-foreground">
                        {t('home.quick_links.subscribe_label')}
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        {t('home.quick_links.subscribe_hint')}
                    </p>
                </div>
            </div>

            {recentlySuccessful ? (
                <div className="rounded-lg bg-green-50 p-4 text-sm font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                    Подписка оформлена. Проверьте почту для подтверждения.
                </div>
            ) : (
                <form onSubmit={submit} className="flex flex-col gap-3">
                    <div className="space-y-1">
                        <Input
                            type="email"
                            placeholder="Email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            disabled={processing}
                            required
                        />
                        {errors.email && (
                            <p className="text-xs text-destructive">
                                {errors.email}
                            </p>
                        )}
                    </div>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full"
                    >
                        {processing && (
                            <Loader2 className="mr-2 size-4 animate-spin" />
                        )}
                        Подписаться
                    </Button>
                </form>
            )}
        </div>
    );
}

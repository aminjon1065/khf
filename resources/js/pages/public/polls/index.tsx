import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';
import { show } from '@/routes/polls';

type PollListItem = {
    id: number;
    slug: string;
    title: string;
    description: string | null;
    type: string;
    type_label: string;
    is_active: boolean;
    has_ended: boolean;
    has_voted: boolean;
    starts_at: string | null;
    ends_at: string | null;
    total_votes: number;
};

export default function PollsIndex({ polls }: { polls: PollListItem[] }) {
    const { locale } = usePage().props as { locale: string };
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('polls.title')} />

            <div className="mx-auto max-w-3xl">
                <h1 className="text-3xl font-semibold">{t('polls.title')}</h1>
                <p className="mt-1 text-muted-foreground">
                    {t('polls.subtitle')}
                </p>

                {polls.length === 0 ? (
                    <p className="mt-8 text-muted-foreground">
                        {t('polls.empty')}
                    </p>
                ) : (
                    <div className="mt-6 space-y-4">
                        {polls.map((poll) => (
                            <Link
                                key={poll.id}
                                href={show({ locale, slug: poll.slug }).url}
                                className="block rounded-lg border p-5 transition-colors hover:border-primary/40 hover:bg-muted/30"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="text-lg font-medium">
                                        {poll.title}
                                    </h2>
                                    <span className="rounded-md bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground">
                                        {poll.type_label}
                                    </span>
                                    {poll.is_active && (
                                        <span className="rounded-md bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-200">
                                            {t('polls.active')}
                                        </span>
                                    )}
                                    {poll.has_ended && (
                                        <span className="rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                            {t('polls.ended')}
                                        </span>
                                    )}
                                </div>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {t('polls.votes', {
                                        count: poll.total_votes,
                                    })}
                                </p>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

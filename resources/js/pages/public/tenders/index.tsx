import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    ChevronLeft,
    ChevronRight,
    Layers,
    Wallet,
} from 'lucide-react';
import type { Paginator } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { show, track } from '@/routes/tenders';

type TenderCard = {
    title: string | null;
    slug: string | null;
    organizer: string | null;
    summary: string | null;
    tender_number: string | null;
    type_label: string;
    budget: string | null;
    lots_count: number;
    published_at: string | null;
    deadline_at: string | null;
};

type PageProps = {
    tenders: Paginator<TenderCard> & {
        prev_page_url: string | null;
        next_page_url: string | null;
    };
};

export default function TendersIndex({ tenders }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('tenders.title')} />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-semibold">
                        {t('tenders.title')}
                    </h1>
                    <p className="mt-1 text-muted-foreground">
                        {t('tenders.subtitle')}
                    </p>
                </div>
                <Button variant="outline" asChild>
                    <Link href={track({ locale }).url}>
                        {t('tenders.track_existing')}
                    </Link>
                </Button>
            </div>

            {tenders.data.length === 0 ? (
                <p className="mt-8 text-muted-foreground">
                    {t('tenders.empty')}
                </p>
            ) : (
                <div className="mt-6 space-y-4">
                    {tenders.data.map((tender) => (
                        <Link
                            key={tender.slug}
                            href={show({ locale, slug: tender.slug ?? '' }).url}
                            className="group block rounded-lg border p-5 transition-shadow hover:shadow-md"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 className="text-lg font-semibold group-hover:text-primary">
                                        {tender.title}
                                    </h2>
                                    <p className="mt-0.5 text-sm text-muted-foreground">
                                        {tender.tender_number && (
                                            <span className="font-mono">
                                                {tender.tender_number}
                                            </span>
                                        )}
                                        {tender.tender_number &&
                                            tender.organizer &&
                                            ' · '}
                                        {tender.organizer}
                                    </p>
                                </div>
                                <span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                    {tender.type_label}
                                </span>
                            </div>

                            {tender.summary && (
                                <p className="mt-3 line-clamp-2 text-sm text-muted-foreground">
                                    {tender.summary}
                                </p>
                            )}

                            <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                                {tender.budget && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <Wallet className="size-4" />
                                        {tender.budget} {t('tenders.currency')}
                                    </span>
                                )}
                                <span className="inline-flex items-center gap-1.5">
                                    <Layers className="size-4" />
                                    {t('tenders.lots', {
                                        count: tender.lots_count,
                                    })}
                                </span>
                                {tender.deadline_at && (
                                    <span className="inline-flex items-center gap-1.5 font-medium text-foreground">
                                        <CalendarClock className="size-4" />
                                        {t('tenders.deadline', {
                                            date: tender.deadline_at,
                                        })}
                                    </span>
                                )}
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            {(tenders.prev_page_url || tenders.next_page_url) && (
                <div className="mt-8 flex items-center justify-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!tenders.prev_page_url}
                        onClick={() =>
                            tenders.prev_page_url &&
                            router.get(tenders.prev_page_url)
                        }
                    >
                        <ChevronLeft className="size-4" />
                        {t('common.back')}
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!tenders.next_page_url}
                        onClick={() =>
                            tenders.next_page_url &&
                            router.get(tenders.next_page_url)
                        }
                    >
                        {t('common.next')}
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            )}
        </>
    );
}

import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Briefcase,
    CalendarClock,
    ChevronLeft,
    ChevronRight,
    MapPin,
} from 'lucide-react';
import type { Paginator } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { show, track } from '@/routes/vacancies';

type VacancyCard = {
    title: string | null;
    slug: string | null;
    department: string | null;
    location: string | null;
    summary: string | null;
    employment_type_label: string;
    positions_count: number;
    published_at: string | null;
    deadline_at: string | null;
};

type PageProps = {
    vacancies: Paginator<VacancyCard> & {
        prev_page_url: string | null;
        next_page_url: string | null;
    };
};

export default function VacanciesIndex({ vacancies }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('vacancies.title')} />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-semibold">
                        {t('vacancies.title')}
                    </h1>
                    <p className="mt-1 text-muted-foreground">
                        {t('vacancies.subtitle')}
                    </p>
                </div>
                <Button variant="outline" asChild>
                    <Link href={track({ locale }).url}>
                        {t('vacancies.track_existing')}
                    </Link>
                </Button>
            </div>

            {vacancies.data.length === 0 ? (
                <p className="mt-8 text-muted-foreground">
                    {t('vacancies.empty')}
                </p>
            ) : (
                <div className="mt-6 space-y-4">
                    {vacancies.data.map((vacancy) => (
                        <Link
                            key={vacancy.slug}
                            href={
                                show({ locale, slug: vacancy.slug ?? '' }).url
                            }
                            className="group block rounded-lg border p-5 transition-shadow hover:shadow-md"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 className="text-lg font-semibold group-hover:text-primary">
                                        {vacancy.title}
                                    </h2>
                                    {vacancy.department && (
                                        <p className="mt-0.5 text-sm text-muted-foreground">
                                            {vacancy.department}
                                        </p>
                                    )}
                                </div>
                                <span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                    {vacancy.employment_type_label}
                                </span>
                            </div>

                            {vacancy.summary && (
                                <p className="mt-3 line-clamp-2 text-sm text-muted-foreground">
                                    {vacancy.summary}
                                </p>
                            )}

                            <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                                {vacancy.location && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <MapPin className="size-4" />
                                        {vacancy.location}
                                    </span>
                                )}
                                <span className="inline-flex items-center gap-1.5">
                                    <Briefcase className="size-4" />
                                    {t('vacancies.positions', {
                                        count: vacancy.positions_count,
                                    })}
                                </span>
                                {vacancy.deadline_at && (
                                    <span className="inline-flex items-center gap-1.5 font-medium text-foreground">
                                        <CalendarClock className="size-4" />
                                        {t('vacancies.deadline', {
                                            date: vacancy.deadline_at,
                                        })}
                                    </span>
                                )}
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            {(vacancies.prev_page_url || vacancies.next_page_url) && (
                <div className="mt-8 flex items-center justify-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!vacancies.prev_page_url}
                        onClick={() =>
                            vacancies.prev_page_url &&
                            router.get(vacancies.prev_page_url)
                        }
                    >
                        <ChevronLeft className="size-4" />
                        {t('common.back')}
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!vacancies.next_page_url}
                        onClick={() =>
                            vacancies.next_page_url &&
                            router.get(vacancies.next_page_url)
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

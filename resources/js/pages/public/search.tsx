import { Head, Link, usePage } from '@inertiajs/react';
import {
    FileText,
    Info,
    BookOpen,
    Briefcase,
    Gavel,
    Users,
    Building2,
    Image,
    HelpCircle,
    BarChart3,
    AlertCircle,
    Search as SearchIcon,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';
import type { SharedData } from '@/types';

interface SearchResult {
    id: number;
    type: string;
    title: string;
    excerpt: string | null;
    highlighted_title?: string | null;
    highlighted_excerpt?: string | null;
    url: string;
    date: string | null;
}

type Pagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

export default function Search({
    query,
    results,
    pagination,
    filters,
    contentTypes,
}: {
    query: string;
    results: SearchResult[];
    pagination?: Pagination;
    filters?: { type?: string | null };
    contentTypes?: string[];
}) {
    const { t } = useTranslations();
    const { locale } = usePage<SharedData>().props;
    const activeType = filters?.type ?? null;

    const buildSearchUrl = (overrides: {
        type?: string | null;
        page?: number;
    }) => {
        const params = new URLSearchParams();

        if (query) {
            params.set('q', query);
        }

        const type = overrides.type !== undefined ? overrides.type : activeType;

        if (type) {
            params.set('type', type);
        }

        const page = overrides.page ?? 1;

        if (page > 1) {
            params.set('page', String(page));
        }

        const qs = params.toString();

        return `/${locale}/search${qs ? `?${qs}` : ''}`;
    };

    const getIcon = (type: string) => {
        switch (type) {
            case 'post':
                return <AlertCircle className="size-5 text-blue-500" />;
            case 'page':
                return <Info className="size-5 text-green-500" />;
            case 'document':
                return <FileText className="size-5 text-orange-500" />;
            case 'guide':
                return <BookOpen className="size-5 text-purple-500" />;
            case 'vacancy':
                return <Briefcase className="size-5 text-primary" />;
            case 'tender':
                return <Gavel className="size-5 text-amber-600" />;
            case 'leader':
                return <Users className="size-5 text-sky-600" />;
            case 'subdivision':
                return <Building2 className="size-5 text-slate-600" />;
            case 'gallery':
                return <Image className="size-5 text-pink-600" />;
            case 'faq':
                return <HelpCircle className="size-5 text-teal-600" />;
            case 'statistic':
                return <BarChart3 className="size-5 text-indigo-600" />;
            default:
                return <FileText className="size-5" />;
        }
    };

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'post':
                return t('nav.news');
            case 'page':
                return t('nav.home');
            case 'document':
                return t('nav.documents');
            case 'guide':
                return t('nav.guides');
            case 'vacancy':
                return t('nav.vacancies');
            case 'tender':
                return t('nav.tenders');
            case 'leader':
                return t('nav.leadership');
            case 'subdivision':
                return t('nav.structure');
            case 'gallery':
                return t('nav.gallery');
            case 'faq':
                return t('nav.faq');
            case 'statistic':
                return t('nav.statistics');
            default:
                return type;
        }
    };

    return (
        <>
            <Head title={`${t('actions.search')} - ${query}`} />

            <div className="mx-auto max-w-3xl py-8">
                <div className="mb-8">
                    <h1 className="flex items-center gap-3 text-3xl font-bold tracking-tight text-foreground">
                        <SearchIcon className="size-8 text-muted-foreground" />
                        {t('actions.search')}
                    </h1>
                    <p className="mt-2 text-lg text-muted-foreground">
                        {query
                            ? t('search.results_for', { query })
                            : t('search.enter_query')}
                    </p>
                    {pagination && pagination.total > 0 && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {t('search.results_count', {
                                count: pagination.total,
                            })}
                        </p>
                    )}
                </div>

                {query && contentTypes && contentTypes.length > 0 && (
                    <div className="mb-6 flex flex-wrap gap-2">
                        <Link
                            href={buildSearchUrl({ type: null, page: 1 })}
                            className={`rounded-full border px-3 py-1 text-sm font-medium transition-colors ${
                                !activeType
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'bg-card text-muted-foreground hover:border-primary/40 hover:text-foreground'
                            }`}
                        >
                            {t('search.filter_all')}
                        </Link>
                        {contentTypes.map((type) => (
                            <Link
                                key={type}
                                href={buildSearchUrl({ type, page: 1 })}
                                className={`rounded-full border px-3 py-1 text-sm font-medium transition-colors ${
                                    activeType === type
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'bg-card text-muted-foreground hover:border-primary/40 hover:text-foreground'
                                }`}
                            >
                                {getTypeLabel(type)}
                            </Link>
                        ))}
                    </div>
                )}

                {query && results.length === 0 && (
                    <div className="rounded-lg border bg-card py-16 text-center shadow-sm">
                        <SearchIcon className="mx-auto mb-4 size-12 text-muted-foreground/30" />
                        <h3 className="text-lg font-medium">
                            {t('table.empty')}
                        </h3>
                        <p className="mt-1 text-muted-foreground">
                            {t('search.no_results_hint')}
                        </p>
                    </div>
                )}

                {results.length > 0 && (
                    <div className="space-y-4">
                        {results.map((result, idx) => (
                            <Link
                                key={`${result.type}-${result.id}-${idx}`}
                                href={result.url}
                                className="group block rounded-xl border bg-card p-4 shadow-sm transition-shadow hover:shadow-md sm:p-6"
                            >
                                <div className="flex gap-4">
                                    <div className="mt-1 h-fit rounded-lg border border-border/50 bg-muted/50 p-2">
                                        {getIcon(result.type)}
                                    </div>
                                    <div className="flex-1">
                                        <div className="mb-1.5 flex items-center gap-2">
                                            <span className="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                                {getTypeLabel(result.type)}
                                            </span>
                                            {result.date && (
                                                <span className="text-xs text-muted-foreground/60">
                                                    &bull; {result.date}
                                                </span>
                                            )}
                                        </div>
                                        <h2
                                            className="text-lg font-semibold text-foreground transition-colors group-hover:text-primary"
                                            dangerouslySetInnerHTML={{
                                                __html:
                                                    result.highlighted_title ??
                                                    result.title,
                                            }}
                                        />
                                        {(result.excerpt ||
                                            result.highlighted_excerpt) && (
                                            <p
                                                className="mt-2 text-sm leading-relaxed text-muted-foreground sm:text-base"
                                                dangerouslySetInnerHTML={{
                                                    __html:
                                                        result.highlighted_excerpt ??
                                                        result.excerpt ??
                                                        '',
                                                }}
                                            />
                                        )}
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}

                {pagination && pagination.last_page > 1 && (
                    <nav
                        className="mt-8 flex items-center justify-between border-t pt-6"
                        aria-label={t('search.pagination')}
                    >
                        {pagination.current_page > 1 ? (
                            <Link
                                href={buildSearchUrl({
                                    page: pagination.current_page - 1,
                                })}
                                className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                            >
                                <ChevronLeft className="size-4" />
                                {t('search.prev_page')}
                            </Link>
                        ) : (
                            <span />
                        )}
                        <span className="text-sm text-muted-foreground">
                            {t('search.page_of', {
                                current: pagination.current_page,
                                total: pagination.last_page,
                            })}
                        </span>
                        {pagination.current_page < pagination.last_page ? (
                            <Link
                                href={buildSearchUrl({
                                    page: pagination.current_page + 1,
                                })}
                                className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                            >
                                {t('search.next_page')}
                                <ChevronRight className="size-4" />
                            </Link>
                        ) : (
                            <span />
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}

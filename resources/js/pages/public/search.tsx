import { Head, Link } from '@inertiajs/react';
import {
    FileText,
    Info,
    BookOpen,
    AlertCircle,
    Search as SearchIcon,
} from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';
import PublicLayout from '@/layouts/public/public-layout';

interface SearchResult {
    id: number;
    type: string;
    title: string;
    excerpt: string | null;
    url: string;
    date: string | null;
}

export default function Search({
    query,
    results,
}: {
    query: string;
    results: SearchResult[];
}) {
    const { t } = useTranslations();

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
            default:
                return type;
        }
    };

    return (
        <PublicLayout>
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
                </div>

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
                                        <h2 className="text-lg font-semibold text-foreground transition-colors group-hover:text-primary">
                                            {result.title}
                                        </h2>
                                        {result.excerpt && (
                                            <p className="mt-2 text-sm leading-relaxed text-muted-foreground sm:text-base">
                                                {result.excerpt}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}

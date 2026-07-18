import { usePage, Link } from '@inertiajs/react';
import {
    Search,
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
    Loader2,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/hooks/use-translations';
import { api as searchApi } from '@/routes/search';
import type { SharedData } from '@/types';

type SearchResultItem = {
    id: number;
    type: string;
    title: string;
    excerpt: string | null;
    highlighted_title?: string | null;
    highlighted_excerpt?: string | null;
    url: string;
    date: string | null;
};

type SearchResponse = {
    data?: SearchResultItem[];
    error?: string;
};

export function GlobalSearchModal({
    isOpen,
    setIsOpen,
}: {
    isOpen: boolean;
    setIsOpen: (open: boolean) => void;
}) {
    const { t } = useTranslations();
    const { locale } = usePage<SharedData>().props;

    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResultItem[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const unavailableMessage = t('search.unavailable');

    // Clear state when the dialog closes so the next open starts fresh — handled here rather than in
    // an effect to avoid a setState-in-effect render cascade.
    const handleOpenChange = (open: boolean) => {
        if (!open) {
            setQuery('');
            setResults([]);
            setError(null);
            setIsLoading(false);
        }

        setIsOpen(open);
    };

    // Keyboard shortcut Cmd+K or Ctrl+K
    useEffect(() => {
        const down = (e: KeyboardEvent) => {
            if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                setIsOpen(true);
            }
        };
        document.addEventListener('keydown', down);

        return () => document.removeEventListener('keydown', down);
    }, [setIsOpen]);

    // Live search with debounce
    useEffect(() => {
        if (!isOpen || query.length < 2) {
            return;
        }

        const controller = new AbortController();
        const timer = setTimeout(async () => {
            setIsLoading(true);
            setError(null);

            try {
                const response = await fetch(
                    searchApi({ locale }, { query: { q: query.trim() } }).url,
                    {
                        headers: { Accept: 'application/json' },
                        signal: controller.signal,
                    },
                );
                const data = (await response
                    .json()
                    .catch(() => ({}))) as SearchResponse;

                if (!response.ok) {
                    setResults([]);
                    setError(data.error ?? unavailableMessage);

                    return;
                }

                setResults(data.data ?? []);
            } catch (error) {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                ) {
                    return;
                }

                setResults([]);
                setError(unavailableMessage);
            } finally {
                if (!controller.signal.aborted) {
                    setIsLoading(false);
                }
            }
        }, 300);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [query, isOpen, locale, unavailableMessage]);

    // Stale results from a longer query stay hidden once the query drops below the 2-char threshold.
    const visibleResults = query.length >= 2 ? results : [];

    const getIcon = (type: string) => {
        switch (type) {
            case 'post':
                return (
                    <AlertCircle
                        className="size-4 text-blue-500"
                        aria-hidden="true"
                    />
                );
            case 'page':
                return (
                    <Info
                        className="size-4 text-green-500"
                        aria-hidden="true"
                    />
                );
            case 'document':
                return (
                    <FileText
                        className="size-4 text-orange-500"
                        aria-hidden="true"
                    />
                );
            case 'guide':
                return (
                    <BookOpen
                        className="size-4 text-purple-500"
                        aria-hidden="true"
                    />
                );
            case 'vacancy':
                return (
                    <Briefcase
                        className="size-4 text-primary"
                        aria-hidden="true"
                    />
                );
            case 'tender':
                return (
                    <Gavel
                        className="size-4 text-amber-600"
                        aria-hidden="true"
                    />
                );
            case 'leader':
                return (
                    <Users className="size-4 text-sky-600" aria-hidden="true" />
                );
            case 'subdivision':
                return (
                    <Building2
                        className="size-4 text-slate-600"
                        aria-hidden="true"
                    />
                );
            case 'gallery':
                return (
                    <Image
                        className="size-4 text-pink-600"
                        aria-hidden="true"
                    />
                );
            case 'faq':
                return (
                    <HelpCircle
                        className="size-4 text-teal-600"
                        aria-hidden="true"
                    />
                );
            case 'statistic':
                return (
                    <BarChart3
                        className="size-4 text-indigo-600"
                        aria-hidden="true"
                    />
                );
            default:
                return <FileText className="size-4" aria-hidden="true" />;
        }
    };

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'post':
                return t('nav.news');
            case 'page':
                return t('nav.home'); // Simplified
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
        <Dialog open={isOpen} onOpenChange={handleOpenChange}>
            <DialogContent className="gap-0 overflow-hidden bg-background/95 p-0 backdrop-blur-xl sm:max-w-[600px]">
                <DialogHeader className="border-b p-4">
                    <DialogTitle className="sr-only">
                        {t('actions.search')}
                    </DialogTitle>
                    <div className="flex items-center gap-3">
                        <Search
                            className="size-5 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <input
                            type="text"
                            autoFocus
                            aria-label={t('actions.search')}
                            placeholder={t('actions.search') + '...'}
                            className="flex-1 bg-transparent text-base outline-none placeholder:text-muted-foreground/60"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                        />
                        {isLoading && (
                            <Loader2
                                className="size-5 shrink-0 animate-spin text-muted-foreground"
                                aria-hidden="true"
                            />
                        )}
                    </div>
                </DialogHeader>

                <div
                    className="max-h-[60vh] overflow-y-auto p-2"
                    aria-live="polite"
                    aria-busy={isLoading}
                >
                    {visibleResults.length > 0 && (
                        <p role="status" className="sr-only">
                            {t('a11y.search_results')}: {visibleResults.length}
                        </p>
                    )}

                    {query.length >= 2 &&
                        results.length === 0 &&
                        !error &&
                        !isLoading && (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                {t('table.empty')}
                            </div>
                        )}

                    {query.length >= 2 && error && !isLoading && (
                        <div
                            role="alert"
                            className="rounded-md border border-destructive/40 bg-destructive/5 px-4 py-6 text-center text-sm text-destructive"
                        >
                            {error}
                        </div>
                    )}

                    {query.length < 2 && (
                        <div className="py-8 text-center text-sm text-muted-foreground/60">
                            {t('search.prompt')}
                        </div>
                    )}

                    <div className="flex flex-col gap-1">
                        {visibleResults.map((result, idx) => (
                            <Link
                                key={`${result.type}-${result.id}-${idx}`}
                                href={result.url}
                                onClick={() => setIsOpen(false)}
                                className="group flex items-start gap-3 rounded-lg p-3 transition-colors hover:bg-muted/60"
                            >
                                <div className="mt-0.5 rounded-md border bg-background p-1.5 shadow-sm">
                                    {getIcon(result.type)}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="mb-1 flex items-center gap-2">
                                        <span className="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase">
                                            {getTypeLabel(result.type)}
                                        </span>
                                        {result.date && (
                                            <span className="text-[10px] text-muted-foreground/60">
                                                &bull; {result.date}
                                            </span>
                                        )}
                                    </div>
                                    <h4 className="truncate text-sm font-medium text-foreground transition-colors group-hover:text-primary">
                                        {result.highlighted_title ? (
                                            <span
                                                dangerouslySetInnerHTML={{
                                                    __html: result.highlighted_title,
                                                }}
                                            />
                                        ) : (
                                            result.title
                                        )}
                                    </h4>
                                    {(result.excerpt ||
                                        result.highlighted_excerpt) && (
                                        <p className="mt-1 line-clamp-2 text-xs leading-relaxed text-muted-foreground">
                                            {result.highlighted_excerpt ? (
                                                <span
                                                    dangerouslySetInnerHTML={{
                                                        __html: result.highlighted_excerpt,
                                                    }}
                                                />
                                            ) : (
                                                result.excerpt
                                            )}
                                        </p>
                                    )}
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

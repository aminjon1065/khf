import { Head, Link } from '@inertiajs/react';
import { FileText, Map, Info, BookOpen, AlertCircle, Search as SearchIcon } from 'lucide-react';
import PublicLayout from '@/layouts/public/public-layout';
import { useTranslations } from '@/hooks/use-translations';

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
            case 'post': return <AlertCircle className="size-5 text-blue-500" />;
            case 'page': return <Info className="size-5 text-green-500" />;
            case 'document': return <FileText className="size-5 text-orange-500" />;
            case 'guide': return <BookOpen className="size-5 text-purple-500" />;
            default: return <FileText className="size-5" />;
        }
    };

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'post': return t('nav.news');
            case 'page': return t('nav.home');
            case 'document': return t('nav.documents');
            case 'guide': return t('nav.guides');
            default: return type;
        }
    };

    return (
        <PublicLayout>
            <Head title={`${t('actions.search')} - ${query}`} />

            <div className="max-w-3xl mx-auto py-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold tracking-tight text-foreground flex items-center gap-3">
                        <SearchIcon className="size-8 text-muted-foreground" />
                        {t('actions.search')}
                    </h1>
                    <p className="text-muted-foreground mt-2 text-lg">
                        {query ? `Результаты по запросу «${query}»` : 'Введите запрос для поиска'}
                    </p>
                </div>

                {query && results.length === 0 && (
                    <div className="text-center py-16 bg-card border rounded-lg shadow-sm">
                        <SearchIcon className="size-12 text-muted-foreground/30 mx-auto mb-4" />
                        <h3 className="text-lg font-medium">{t('table.empty')}</h3>
                        <p className="text-muted-foreground mt-1">Попробуйте изменить поисковый запрос или использовать другие ключевые слова.</p>
                    </div>
                )}

                {results.length > 0 && (
                    <div className="space-y-4">
                        {results.map((result, idx) => (
                            <Link
                                key={`${result.type}-${result.id}-${idx}`}
                                href={result.url}
                                className="block p-4 sm:p-6 bg-card border rounded-xl shadow-sm hover:shadow-md transition-shadow group"
                            >
                                <div className="flex gap-4">
                                    <div className="mt-1 bg-muted/50 p-2 rounded-lg h-fit border border-border/50">
                                        {getIcon(result.type)}
                                    </div>
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2 mb-1.5">
                                            <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                                {getTypeLabel(result.type)}
                                            </span>
                                            {result.date && (
                                                <span className="text-xs text-muted-foreground/60">
                                                    &bull; {result.date}
                                                </span>
                                            )}
                                        </div>
                                        <h2 className="text-lg font-semibold text-foreground group-hover:text-primary transition-colors">
                                            {result.title}
                                        </h2>
                                        {result.excerpt && (
                                            <p className="text-muted-foreground mt-2 leading-relaxed text-sm sm:text-base">
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

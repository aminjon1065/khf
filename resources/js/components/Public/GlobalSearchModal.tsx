import { useEffect, useState } from 'react';
import { usePage, Link } from '@inertiajs/react';
import { Search, FileText, Map, Info, BookOpen, AlertCircle, X, Loader2 } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/hooks/use-translations';
import { api as searchApi } from '@/routes/search';
import { SharedData } from '@/types';

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
    const [results, setResults] = useState<any[]>([]);
    const [isLoading, setIsLoading] = useState(false);

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
        if (!isOpen) {
            setQuery('');
            setResults([]);
            return;
        }

        if (query.length < 2) {
            setResults([]);
            return;
        }

        const timer = setTimeout(async () => {
            setIsLoading(true);
            try {
                const response = await fetch(`${searchApi({ locale }).url}?q=${encodeURIComponent(query)}`);
                const data = await response.json();
                setResults(data.data || []);
            } catch (error) {
                console.error('Search error', error);
            } finally {
                setIsLoading(false);
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [query, isOpen, locale]);

    const getIcon = (type: string) => {
        switch (type) {
            case 'post': return <AlertCircle className="size-4 text-blue-500" />;
            case 'page': return <Info className="size-4 text-green-500" />;
            case 'document': return <FileText className="size-4 text-orange-500" />;
            case 'guide': return <BookOpen className="size-4 text-purple-500" />;
            default: return <FileText className="size-4" />;
        }
    };

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'post': return t('nav.news');
            case 'page': return t('nav.home'); // Simplified
            case 'document': return t('nav.documents');
            case 'guide': return t('nav.guides');
            default: return type;
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogContent className="sm:max-w-[600px] p-0 overflow-hidden gap-0 bg-background/95 backdrop-blur-xl">
                <DialogHeader className="p-4 border-b">
                    <DialogTitle className="sr-only">Search</DialogTitle>
                    <div className="flex items-center gap-3">
                        <Search className="size-5 text-muted-foreground shrink-0" />
                        <input
                            type="text"
                            autoFocus
                            placeholder={t('actions.search') + '...'}
                            className="flex-1 bg-transparent outline-none text-base placeholder:text-muted-foreground/60"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                        />
                        {isLoading && <Loader2 className="size-5 animate-spin text-muted-foreground shrink-0" />}
                        <div className="hidden sm:flex text-[10px] text-muted-foreground border px-1.5 py-0.5 rounded bg-muted/50">
                            ESC
                        </div>
                    </div>
                </DialogHeader>

                <div className="max-h-[60vh] overflow-y-auto p-2">
                    {query.length >= 2 && results.length === 0 && !isLoading && (
                        <div className="text-center py-8 text-muted-foreground text-sm">
                            {t('table.empty')}
                        </div>
                    )}
                    
                    {query.length < 2 && (
                        <div className="text-center py-8 text-muted-foreground/60 text-sm">
                            Введите поисковый запрос...
                        </div>
                    )}

                    <div className="flex flex-col gap-1">
                        {results.map((result, idx) => (
                            <Link
                                key={`${result.type}-${result.id}-${idx}`}
                                href={result.url}
                                onClick={() => setIsOpen(false)}
                                className="flex items-start gap-3 p-3 rounded-lg hover:bg-muted/60 transition-colors group"
                            >
                                <div className="mt-0.5 bg-background p-1.5 rounded-md shadow-sm border">
                                    {getIcon(result.type)}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 mb-1">
                                        <span className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">
                                            {getTypeLabel(result.type)}
                                        </span>
                                        {result.date && (
                                            <span className="text-[10px] text-muted-foreground/60">
                                                &bull; {result.date}
                                            </span>
                                        )}
                                    </div>
                                    <h4 className="text-sm font-medium text-foreground truncate group-hover:text-primary transition-colors">
                                        {result.title}
                                    </h4>
                                    {result.excerpt && (
                                        <p className="text-xs text-muted-foreground line-clamp-2 mt-1 leading-relaxed">
                                            {result.excerpt}
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

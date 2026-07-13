import { Copy, Search } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { mediaFocalPosition } from '@/components/admin/focal-point-picker';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

export type MediaLibraryItem = {
    id: number;
    name: string;
    alt_text: string | null;
    translations: Record<string, { alt_text: string | null }>;
    tags: string[];
    usages: Array<{
        label: string;
        context: string;
        edit_url: string | null;
    }>;
    media_folder_id: number | null;
    focal_x: number;
    focal_y: number;
    is_image: boolean;
    original_url: string | null;
    thumb_url: string | null;
    human_size: string | null;
    mime_type: string | null;
    media: Array<{ id: number; original_url: string; name: string }>;
};

export type MediaLibraryFilters = {
    search: string;
    type: '' | 'image' | 'document';
    folder_id: string;
    tag: string;
};

type MediaLibraryResponse = {
    data: MediaLibraryItem[];
    meta?: {
        current_page: number;
        last_page: number;
    };
};

type UseMediaLibraryOptions = {
    enabled?: boolean;
    filters?: MediaLibraryFilters;
    imagesOnly?: boolean;
};

export function useMediaLibrary({
    enabled = true,
    filters,
    imagesOnly = false,
}: UseMediaLibraryOptions = {}) {
    const [items, setItems] = useState<MediaLibraryItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);

    const search = filters?.search ?? '';
    const type = imagesOnly ? 'image' : (filters?.type ?? '');
    const folderId = filters?.folder_id ?? 'all';
    const tag = filters?.tag ?? '';

    const load = useCallback(
        (nextPage = 1, append = false) => {
            if (!enabled) {
                return;
            }

            setLoading(true);

            const params = new URLSearchParams();

            if (search.trim() !== '') {
                params.set('search', search.trim());
            }

            if (type !== '') {
                params.set('type', type);
            }

            if (folderId !== '' && folderId !== 'all') {
                params.set('folder_id', folderId);
            }

            if (tag.trim() !== '') {
                params.set('tag', tag.trim());
            }

            params.set('page', String(nextPage));

            fetch(`/admin/api/media?${params.toString()}`)
                .then((response) => response.json())
                .then((data: MediaLibraryResponse) => {
                    setItems((current) =>
                        append
                            ? [...current, ...(data.data ?? [])]
                            : (data.data ?? []),
                    );
                    setPage(data.meta?.current_page ?? nextPage);
                    setLastPage(data.meta?.last_page ?? 1);
                })
                .catch(() => {
                    if (!append) {
                        setItems([]);
                    }
                })
                .finally(() => setLoading(false));
        },
        [enabled, folderId, search, tag, type],
    );

    useEffect(() => {
        if (!enabled) {
            return;
        }

        const timer = window.setTimeout(() => load(1, false), search ? 250 : 0);

        return () => window.clearTimeout(timer);
    }, [enabled, folderId, load, search, tag, type]);

    return {
        items,
        loading,
        page,
        lastPage,
        loadMore: () => {
            if (page < lastPage && !loading) {
                load(page + 1, true);
            }
        },
        reload: () => load(1, false),
    };
}

export function MediaBrowserFilters({
    search,
    type,
    onSearchChange,
    onTypeChange,
    searchId = 'media-search',
    hideTypeFilter = false,
}: {
    search: string;
    type: '' | 'image' | 'document';
    onSearchChange: (value: string) => void;
    onTypeChange: (value: '' | 'image' | 'document') => void;
    searchId?: string;
    hideTypeFilter?: boolean;
}) {
    return (
        <div className="flex flex-col gap-3 sm:flex-row">
            <div className="relative flex-1">
                <Search
                    className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden
                />
                <Input
                    id={searchId}
                    aria-label="Поиск по названию или alt-тексту"
                    value={search}
                    onChange={(event) => onSearchChange(event.target.value)}
                    placeholder="Поиск по названию или alt-тексту…"
                    className="pl-8"
                />
            </div>
            {!hideTypeFilter && (
                <Select
                    value={type === '' ? 'all' : type}
                    onValueChange={(value) =>
                        onTypeChange(
                            value === 'all'
                                ? ''
                                : (value as 'image' | 'document'),
                        )
                    }
                >
                    <SelectTrigger
                        className="w-full sm:w-44"
                        aria-label="Тип файла"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Все типы</SelectItem>
                        <SelectItem value="image">Изображения</SelectItem>
                        <SelectItem value="document">Документы</SelectItem>
                    </SelectContent>
                </Select>
            )}
        </div>
    );
}

export function MediaBrowserGrid({
    items,
    loading,
    selectedId,
    selectedIds = [],
    onSelect,
    onPick,
    onToggleSelect,
    emptyMessage = 'Файлов не найдено',
}: {
    items: MediaLibraryItem[];
    loading?: boolean;
    selectedId?: number | null;
    selectedIds?: number[];
    onSelect?: (item: MediaLibraryItem) => void;
    onPick?: (item: MediaLibraryItem) => void;
    onToggleSelect?: (item: MediaLibraryItem) => void;
    emptyMessage?: string;
}) {
    if (loading && items.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                Загрузка…
            </p>
        );
    }

    if (items.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-muted-foreground">
                {emptyMessage}
            </p>
        );
    }

    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            {items.map((item) => {
                const previewUrl = item.thumb_url ?? item.original_url;
                const isSelected = selectedId === item.id;
                const isChecked = selectedIds.includes(item.id);
                const clickable = onPick ?? onSelect;

                return (
                    <div key={item.id} className="relative">
                        {onToggleSelect ? (
                            <label className="absolute top-2 left-2 z-10 flex size-5 cursor-pointer items-center justify-center rounded border border-white/80 bg-black/40">
                                <input
                                    type="checkbox"
                                    className="size-3.5 accent-primary"
                                    checked={isChecked}
                                    onChange={() => onToggleSelect(item)}
                                    onClick={(event) => event.stopPropagation()}
                                />
                            </label>
                        ) : null}
                        <button
                            type="button"
                            title={item.name}
                            onClick={() => clickable?.(item)}
                            className={cn(
                                'group relative aspect-square w-full overflow-hidden rounded-lg border bg-muted text-left transition focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                isSelected || isChecked
                                    ? 'border-primary ring-2 ring-primary ring-offset-2'
                                    : 'border-border hover:border-primary/50',
                                !clickable && 'cursor-default',
                            )}
                        >
                            {item.is_image && previewUrl ? (
                                <img
                                    src={previewUrl}
                                    alt={item.alt_text || item.name}
                                    className="size-full object-cover transition-transform group-hover:scale-105"
                                    style={{
                                        objectPosition: mediaFocalPosition(
                                            item.focal_x,
                                            item.focal_y,
                                        ),
                                    }}
                                />
                            ) : (
                                <div className="flex size-full flex-col items-center justify-center p-2 text-center text-xs break-all text-muted-foreground">
                                    <div className="mb-1 text-sm font-bold uppercase">
                                        {item.mime_type?.split('/')[1] ??
                                            'file'}
                                    </div>
                                    <span className="line-clamp-3">
                                        {item.name}
                                    </span>
                                </div>
                            )}
                            <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 transition-opacity group-hover:opacity-100">
                                <p className="truncate text-xs text-white">
                                    {item.name}
                                </p>
                            </div>
                        </button>
                    </div>
                );
            })}
        </div>
    );
}

export function MediaBrowserDetail({
    item,
    onCopyUrl,
}: {
    item: MediaLibraryItem | null;
    onCopyUrl?: (url: string) => void;
}) {
    if (item === null) {
        return (
            <div className="flex h-full min-h-48 items-center justify-center rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                Выберите файл для просмотра и редактирования
            </div>
        );
    }

    const previewUrl = item.original_url ?? item.thumb_url;

    return (
        <div className="space-y-4 rounded-lg border border-border bg-card p-4">
            {item.is_image && previewUrl ? (
                <img
                    src={previewUrl}
                    alt={item.alt_text || item.name}
                    className="max-h-56 w-full rounded-md object-cover"
                    style={{
                        objectPosition: mediaFocalPosition(
                            item.focal_x,
                            item.focal_y,
                        ),
                    }}
                />
            ) : (
                <div className="flex h-32 items-center justify-center rounded-md bg-muted text-sm text-muted-foreground">
                    {item.mime_type ?? 'Файл'}
                </div>
            )}

            <dl className="space-y-2 text-sm">
                <div>
                    <dt className="text-muted-foreground">Название</dt>
                    <dd className="font-medium break-all">{item.name}</dd>
                </div>
                {item.human_size && (
                    <div>
                        <dt className="text-muted-foreground">Размер</dt>
                        <dd>{item.human_size}</dd>
                    </div>
                )}
                {item.original_url && (
                    <div>
                        <dt className="text-muted-foreground">URL</dt>
                        <dd className="flex items-start gap-2">
                            <span className="break-all">
                                {item.original_url}
                            </span>
                            {onCopyUrl && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="shrink-0"
                                    aria-label="Копировать URL"
                                    onClick={() =>
                                        onCopyUrl(item.original_url!)
                                    }
                                >
                                    <Copy className="size-4" />
                                </Button>
                            )}
                        </dd>
                    </div>
                )}
                {item.tags.length > 0 && (
                    <div>
                        <dt className="text-muted-foreground">Теги</dt>
                        <dd className="flex flex-wrap gap-1 pt-1">
                            {item.tags.map((tag) => (
                                <span
                                    key={tag}
                                    className="rounded-full bg-muted px-2 py-0.5 text-xs"
                                >
                                    {tag}
                                </span>
                            ))}
                        </dd>
                    </div>
                )}
                {item.usages.length > 0 && (
                    <div>
                        <dt className="text-muted-foreground">
                            Используется в
                        </dt>
                        <dd className="space-y-1 pt-1">
                            {item.usages.map((usage) => (
                                <div key={`${usage.context}-${usage.label}`}>
                                    {usage.edit_url ? (
                                        <a
                                            href={usage.edit_url}
                                            className="text-sm text-primary hover:underline"
                                        >
                                            {usage.label}
                                        </a>
                                    ) : (
                                        <span className="text-sm">
                                            {usage.label}
                                        </span>
                                    )}
                                </div>
                            ))}
                        </dd>
                    </div>
                )}
            </dl>
        </div>
    );
}

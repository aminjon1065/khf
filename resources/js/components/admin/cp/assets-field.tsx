import { ImageIcon, Upload, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
    MediaBrowserFilters,
    MediaBrowserGrid,
    type MediaLibraryItem,
    useMediaLibrary,
} from '@/components/admin/media-browser';
import { CpStack } from '@/components/admin/cp/stack';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

/**
 * Statamic-style assets fieldtype for a single image (e.g. a cover). The editor can either upload a
 * new file or pick an existing asset from the media library, shown in a {@link CpStack}. Upload
 * feeds back a `File`; a library pick feeds back the chosen media-library id (the backend copies it
 * into the field's collection — D-22). Clearing flags removal. Preview precedence: new upload →
 * picked asset → the currently-saved image.
 */
export function CpAssetsField({
    label,
    instructions,
    error,
    currentUrl,
    file,
    mediaId,
    removed,
    onUpload,
    onPickAsset,
    onClear,
}: {
    label: string;
    instructions?: string;
    error?: string;
    currentUrl: string | null;
    file: File | null;
    mediaId: number | null;
    removed: boolean;
    onUpload: (file: File | null) => void;
    onPickAsset: (asset: { id: number; url: string } | null) => void;
    onClear: () => void;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(false);
    const [pickedUrl, setPickedUrl] = useState<string | null>(null);
    const [search, setSearch] = useState('');
    const [type, setType] = useState<'' | 'image' | 'document'>('image');

    const { items, loading, page, lastPage, loadMore } = useMediaLibrary({
        enabled: open,
        filters: { search, type },
        imagesOnly: true,
    });

    const localPreview = useMemo(
        () => (file ? URL.createObjectURL(file) : null),
        [file],
    );

    useEffect(
        () => () => {
            if (localPreview) {
                URL.revokeObjectURL(localPreview);
            }
        },
        [localPreview],
    );

    const preview = removed
        ? null
        : (localPreview ?? (mediaId !== null ? pickedUrl : null) ?? currentUrl);

    const openLibrary = () => {
        setSearch('');
        setOpen(true);
    };

    const pick = (item: MediaLibraryItem) => {
        const url = item.original_url ?? item.thumb_url;

        if (url !== null) {
            setPickedUrl(url);
            onPickAsset({ id: item.id, url });
        }

        setOpen(false);
    };

    const handleUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
        setPickedUrl(null);
        onUpload(event.target.files?.[0] ?? null);
    };

    const clear = () => {
        setPickedUrl(null);

        if (inputRef.current) {
            inputRef.current.value = '';
        }

        onClear();
    };

    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {instructions && (
                <p className="text-xs text-muted-foreground">{instructions}</p>
            )}

            {preview ? (
                <div className="relative w-fit">
                    <img
                        src={preview}
                        alt=""
                        className="h-32 w-full max-w-xs rounded-md border border-border object-cover"
                    />
                    <button
                        type="button"
                        aria-label="Убрать"
                        onClick={clear}
                        className="absolute -top-2 -right-2 rounded-full border border-border bg-card p-1 text-muted-foreground shadow-sm transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <X className="size-4" />
                    </button>
                </div>
            ) : (
                <div className="flex h-32 w-full max-w-xs items-center justify-center rounded-md border border-dashed border-border text-muted-foreground">
                    <ImageIcon className="size-8" />
                </div>
            )}

            <div className="flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => inputRef.current?.click()}
                >
                    <Upload className="size-4" /> Загрузить
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={openLibrary}
                >
                    <ImageIcon className="size-4" /> Медиатека
                </Button>
            </div>
            <input
                ref={inputRef}
                type="file"
                accept="image/*"
                className="hidden"
                onChange={handleUpload}
            />

            <InputError message={error} />

            <CpStack open={open} onOpenChange={setOpen} title="Медиабиблиотека">
                <div className="space-y-3">
                    <MediaBrowserFilters
                        search={search}
                        type={type}
                        onSearchChange={setSearch}
                        onTypeChange={setType}
                        searchId="assets-media-search"
                    />

                    <MediaBrowserGrid
                        items={items}
                        loading={loading}
                        onPick={pick}
                    />

                    {page < lastPage && (
                        <div className="flex justify-center">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={loading}
                                onClick={loadMore}
                            >
                                {loading ? 'Загрузка…' : 'Показать ещё'}
                            </Button>
                        </div>
                    )}
                </div>
            </CpStack>
        </div>
    );
}

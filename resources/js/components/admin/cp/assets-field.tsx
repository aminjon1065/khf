import { ImageIcon, Search, Upload, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { CpStack } from '@/components/admin/cp/stack';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type MediaItem = {
    id: number;
    name: string;
    media: Array<{ id: number; original_url: string }>;
};

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
    const [items, setItems] = useState<MediaItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [query, setQuery] = useState('');

    // Object URL for the locally-chosen file, revoked when the file changes or the field unmounts.
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

    const loadLibrary = () => {
        setLoading(true);
        fetch('/admin/api/media')
            .then((res) => res.json())
            .then((data: { data?: MediaItem[] }) => setItems(data.data ?? []))
            .catch(() => setItems([]))
            .finally(() => setLoading(false));
    };

    const openLibrary = () => {
        setQuery('');
        setOpen(true);

        if (items.length === 0) {
            loadLibrary();
        }
    };

    const pick = (item: MediaItem) => {
        const url = item.media[0]?.original_url ?? null;

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

    const q = query.trim().toLowerCase();
    const filtered =
        q === ''
            ? items
            : items.filter((item) => item.name.toLowerCase().includes(q));

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
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            autoFocus
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Поиск…"
                            className="pl-8"
                        />
                    </div>

                    {loading ? (
                        <p className="py-8 text-center text-sm text-muted-foreground">
                            Загрузка…
                        </p>
                    ) : filtered.length === 0 ? (
                        <p className="py-8 text-center text-sm text-muted-foreground">
                            Файлов не найдено
                        </p>
                    ) : (
                        <div className="grid grid-cols-3 gap-2">
                            {filtered.map((item) => (
                                <button
                                    key={item.id}
                                    type="button"
                                    onClick={() => pick(item)}
                                    title={item.name}
                                    className="group overflow-hidden rounded-md border border-border focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    <img
                                        src={item.media[0]?.original_url}
                                        alt={item.name}
                                        className="aspect-square w-full object-cover transition-transform group-hover:scale-105"
                                    />
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </CpStack>
        </div>
    );
}

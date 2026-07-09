import { Head, router, useForm } from '@inertiajs/react';
import { FolderInput, Scissors, Trash2, UploadCloud } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { FocalPointPicker } from '@/components/admin/focal-point-picker';
import {
    MediaFolderTree,
    type MediaFolderNode,
} from '@/components/admin/media-folder-tree';
import {
    MediaBrowserDetail,
    MediaBrowserFilters,
    MediaBrowserGrid,
    type MediaLibraryFilters,
    type MediaLibraryItem,
} from '@/components/admin/media-browser';
import { CpLocaleTabs } from '@/components/admin/cp/publish-form';
import { MediaLibraryHelp } from '@/components/admin/cp/content-help-topics';
import { ImageCropModal } from '@/components/admin/image-crop-modal';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes/admin';
import {
    bulkDestroy,
    bulkMove,
    destroy as mediaDestroy,
    index as mediaIndex,
    store as mediaStore,
    update as mediaUpdate,
} from '@/routes/admin/media';

type LocaleOption = { code: string; native_name: string };

type Props = {
    mediaFiles: {
        data: MediaLibraryItem[];
        links: { url: string | null; label: string; active: boolean }[];
        meta?: {
            current_page: number;
            last_page: number;
            total: number;
        };
    };
    folders: MediaFolderNode[];
    locales: LocaleOption[];
    defaultLocale: string;
    filters: MediaLibraryFilters;
};

function flatFolderOptions(
    nodes: MediaFolderNode[],
    depth = 0,
): Array<{ id: number; label: string }> {
    return nodes.flatMap((node) => [
        { id: node.id, label: `${'— '.repeat(depth)}${node.name}` },
        ...flatFolderOptions(node.children, depth + 1),
    ]);
}

function buildTranslations(
    item: MediaLibraryItem | null,
    locales: LocaleOption[],
): Record<string, { alt_text: string }> {
    const translations: Record<string, { alt_text: string }> = {};

    locales.forEach((locale) => {
        translations[locale.code] = {
            alt_text:
                item?.translations?.[locale.code]?.alt_text ??
                item?.alt_text ??
                '',
        };
    });

    return translations;
}

export default function MediaLibraryIndex({
    mediaFiles,
    folders: initialFolders,
    locales,
    defaultLocale,
    filters,
}: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [selected, setSelected] = useState<MediaLibraryItem | null>(null);
    const [bulkSelected, setBulkSelected] = useState<number[]>([]);
    const [activeLocale, setActiveLocale] = useState(defaultLocale);
    const [bulkMoveFolderId, setBulkMoveFolderId] = useState<string>('none');
    const [folders, setFolders] = useState(initialFolders);
    const [cropImage, setCropImage] = useState<{
        id: number;
        url: string;
    } | null>(null);

    useEffect(() => {
        setFolders(initialFolders);
    }, [initialFolders]);

    const selectedItem = useMemo(() => {
        if (selected === null) {
            return null;
        }

        return mediaFiles.data.find((item) => item.id === selected.id) ?? selected;
    }, [mediaFiles.data, selected]);

    const detailForm = useForm({
        name: selectedItem?.name ?? '',
        translations: buildTranslations(selectedItem, locales),
        tags: selectedItem?.tags ?? [],
        media_folder_id: selectedItem?.media_folder_id ?? null,
        focal_x: selectedItem?.focal_x ?? 50,
        focal_y: selectedItem?.focal_y ?? 50,
    });

    useEffect(() => {
        if (selectedItem === null) {
            return;
        }

        detailForm.setData({
            name: selectedItem.name,
            translations: buildTranslations(selectedItem, locales),
            tags: selectedItem.tags,
            media_folder_id: selectedItem.media_folder_id,
            focal_x: selectedItem.focal_x,
            focal_y: selectedItem.focal_y,
        });
    }, [selectedItem?.id, locales]);

    const folderOptions = useMemo(() => flatFolderOptions(folders), [folders]);

    const applyFilters = (next: Partial<MediaLibraryFilters>) => {
        router.get(
            mediaIndex().url,
            {
                search: next.search ?? filters.search,
                type: next.type ?? filters.type,
                folder_id: next.folder_id ?? filters.folder_id,
                tag: next.tag ?? filters.tag,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const toggleBulkSelect = (item: MediaLibraryItem) => {
        setBulkSelected((current) =>
            current.includes(item.id)
                ? current.filter((id) => id !== item.id)
                : [...current, item.id],
        );
    };

    const bulkDelete = () => {
        if (bulkSelected.length === 0) {
            return;
        }

        if (
            !confirm(
                `Удалить ${bulkSelected.length} файл(ов) навсегда? Это действие необратимо.`,
            )
        ) {
            return;
        }

        router.post(
            bulkDestroy().url,
            { ids: bulkSelected },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Файлы удалены');
                    setBulkSelected([]);
                    setSelected(null);
                },
            },
        );
    };

    const bulkMoveFiles = () => {
        if (bulkSelected.length === 0) {
            return;
        }

        router.post(
            bulkMove().url,
            {
                ids: bulkSelected,
                folder_id:
                    bulkMoveFolderId === 'none'
                        ? null
                        : Number(bulkMoveFolderId),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Файлы перемещены');
                    setBulkSelected([]);
                },
            },
        );
    };

    const setTranslationAlt = (locale: string, alt_text: string) => {
        detailForm.setData('translations', {
            ...detailForm.data.translations,
            [locale]: { alt_text },
        });
    };

    const handleUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
        if (!event.target.files || event.target.files.length === 0) {
            return;
        }

        const file = event.target.files[0];
        const formData = new FormData();
        formData.append('file', file);

        if (filters.folder_id !== 'all' && filters.folder_id !== '0') {
            formData.append('folder_id', filters.folder_id);
        }

        setUploading(true);
        router.post(mediaStore().url, formData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Файл загружен');

                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
            onError: () => toast.error('Ошибка при загрузке файла'),
            onFinish: () => setUploading(false),
        });
    };

    const handleDelete = (id: number) => {
        if (!confirm('Удалить этот файл навсегда?')) {
            return;
        }

        router.delete(mediaDestroy(id).url, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Файл удалён');

                if (selected?.id === id) {
                    setSelected(null);
                }
            },
        });
    };

    const handleCropComplete = (blob: Blob) => {
        const formData = new FormData();
        formData.append(
            'file',
            new File([blob], 'cropped-image.jpg', { type: 'image/jpeg' }),
        );

        if (filters.folder_id !== 'all' && filters.folder_id !== '0') {
            formData.append('folder_id', filters.folder_id);
        }

        router.post(mediaStore().url, formData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Обрезанное изображение сохранено');
                setCropImage(null);
            },
            onError: () => toast.error('Ошибка при сохранении'),
        });
    };

    const saveDetails = () => {
        if (selectedItem === null) {
            return;
        }

        detailForm.put(mediaUpdate(selectedItem.id).url, {
            preserveScroll: true,
            onSuccess: () => toast.success('Сохранено'),
        });
    };

    const copyUrl = async (url: string) => {
        try {
            await navigator.clipboard.writeText(url);
            toast.success('URL скопирован');
        } catch {
            toast.error('Не удалось скопировать URL');
        }
    };

    return (
        <div className="p-4 sm:p-6">
            <Head title="Медиабиблиотека" />

            <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Медиабиблиотека
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Папки, точка фокуса, alt-тексты и повторное использование
                        файлов
                    </p>
                </div>
                <Button
                    onClick={() => fileInputRef.current?.click()}
                    disabled={uploading}
                >
                    <UploadCloud className="size-4" />
                    {uploading ? 'Загрузка…' : 'Добавить файл'}
                </Button>
                <input
                    type="file"
                    className="hidden"
                    ref={fileInputRef}
                    onChange={handleUpload}
                />
            </div>

            <div className="mb-4">
                <MediaLibraryHelp />
            </div>

            <div className="mb-4 space-y-3">
                <MediaBrowserFilters
                    search={filters.search}
                    type={filters.type}
                    onSearchChange={(search) => applyFilters({ search })}
                    onTypeChange={(type) => applyFilters({ type })}
                />
                <div className="relative max-w-sm">
                    <FolderInput
                        className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden
                    />
                    <Input
                        aria-label="Фильтр по тегу"
                        value={filters.tag}
                        onChange={(event) =>
                            applyFilters({ tag: event.target.value })
                        }
                        placeholder="Фильтр по тегу…"
                        className="pl-8"
                    />
                </div>
            </div>

            <div className="grid gap-6 xl:grid-cols-[240px_minmax(0,1fr)_320px]">
                <MediaFolderTree
                    folders={folders}
                    activeFolderId={filters.folder_id}
                    onSelect={(folder_id) => applyFilters({ folder_id })}
                    onFoldersChange={setFolders}
                />

                <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
                    {bulkSelected.length > 0 && (
                        <div className="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-primary/20 bg-primary/5 p-3">
                            <span className="text-sm font-medium">
                                Выбрано: {bulkSelected.length}
                            </span>
                            <Select
                                value={bulkMoveFolderId}
                                onValueChange={setBulkMoveFolderId}
                            >
                                <SelectTrigger className="h-8 w-44">
                                    <SelectValue placeholder="Папка" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Без папки
                                    </SelectItem>
                                    {folderOptions.map((folder) => (
                                        <SelectItem
                                            key={folder.id}
                                            value={String(folder.id)}
                                        >
                                            {folder.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={bulkMoveFiles}
                            >
                                Переместить
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="destructive"
                                onClick={bulkDelete}
                            >
                                Удалить
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={() => setBulkSelected([])}
                            >
                                Снять выделение
                            </Button>
                        </div>
                    )}

                    <MediaBrowserGrid
                        items={mediaFiles.data}
                        selectedId={selectedItem?.id ?? null}
                        selectedIds={bulkSelected}
                        onToggleSelect={toggleBulkSelect}
                        onSelect={(item) => {
                            setSelected(item);
                        }}
                        emptyMessage={
                            filters.search || filters.type
                                ? 'По вашему запросу ничего не найдено'
                                : 'Файлов нет. Загрузите первый файл.'
                        }
                    />

                    {mediaFiles.meta && mediaFiles.meta.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-between gap-3 text-sm text-muted-foreground">
                            <span>
                                Страница {mediaFiles.meta.current_page} из{' '}
                                {mediaFiles.meta.last_page}
                            </span>
                            <div className="flex gap-2">
                                {mediaFiles.links[0]?.url && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                mediaFiles.links[0].url!,
                                                {},
                                                {
                                                    preserveState: true,
                                                    preserveScroll: true,
                                                },
                                            )
                                        }
                                    >
                                        Назад
                                    </Button>
                                )}
                                {mediaFiles.links[
                                    mediaFiles.links.length - 1
                                ]?.url && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                mediaFiles.links[
                                                    mediaFiles.links.length - 1
                                                ].url!,
                                                {},
                                                {
                                                    preserveState: true,
                                                    preserveScroll: true,
                                                },
                                            )
                                        }
                                    >
                                        Вперёд
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                <div className="space-y-4">
                    <MediaBrowserDetail
                        item={selectedItem}
                        onCopyUrl={copyUrl}
                    />

                    {selectedItem && (
                        <div className="space-y-3 rounded-lg border border-border bg-card p-4">
                            {selectedItem.is_image && selectedItem.original_url && (
                                <FocalPointPicker
                                    imageUrl={selectedItem.original_url}
                                    focalX={detailForm.data.focal_x}
                                    focalY={detailForm.data.focal_y}
                                    onChange={(focal_x, focal_y) =>
                                        detailForm.setData({
                                            ...detailForm.data,
                                            focal_x,
                                            focal_y,
                                        })
                                    }
                                />
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="media-name">Название</Label>
                                <Input
                                    id="media-name"
                                    value={detailForm.data.name}
                                    onChange={(event) =>
                                        detailForm.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="media-folder">Папка</Label>
                                <Select
                                    value={
                                        detailForm.data.media_folder_id === null
                                            ? 'none'
                                            : String(
                                                  detailForm.data
                                                      .media_folder_id,
                                              )
                                    }
                                    onValueChange={(value) =>
                                        detailForm.setData(
                                            'media_folder_id',
                                            value === 'none'
                                                ? null
                                                : Number(value),
                                        )
                                    }
                                >
                                    <SelectTrigger id="media-folder">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Без папки
                                        </SelectItem>
                                        {folderOptions.map((folder) => (
                                            <SelectItem
                                                key={folder.id}
                                                value={String(folder.id)}
                                            >
                                                {folder.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Alt-текст по языкам</Label>
                                <CpLocaleTabs
                                    locales={locales}
                                    active={activeLocale}
                                    onChange={setActiveLocale}
                                    isComplete={(code) =>
                                        Boolean(
                                            detailForm.data.translations[code]
                                                ?.alt_text,
                                        )
                                    }
                                />
                                <Input
                                    id="media-alt"
                                    value={
                                        detailForm.data.translations[
                                            activeLocale
                                        ]?.alt_text ?? ''
                                    }
                                    onChange={(event) =>
                                        setTranslationAlt(
                                            activeLocale,
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Описание для доступности и SEO"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="media-tags">Теги</Label>
                                <Input
                                    id="media-tags"
                                    value={detailForm.data.tags.join(', ')}
                                    onChange={(event) =>
                                        detailForm.setData(
                                            'tags',
                                            event.target.value
                                                .split(',')
                                                .map((tag) => tag.trim())
                                                .filter(Boolean),
                                        )
                                    }
                                    placeholder="баннер, hero, новости"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Через запятую — для поиска и группировки
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    onClick={saveDetails}
                                    disabled={detailForm.processing}
                                >
                                    Сохранить
                                </Button>
                                {selectedItem.is_image &&
                                    selectedItem.original_url && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                setCropImage({
                                                    id: selectedItem.id,
                                                    url: selectedItem.original_url!,
                                                })
                                            }
                                        >
                                            <Scissors className="size-4" />
                                            Кроп
                                        </Button>
                                    )}
                                <Button
                                    type="button"
                                    variant="destructive"
                                    onClick={() =>
                                        handleDelete(selectedItem.id)
                                    }
                                >
                                    <Trash2 className="size-4" />
                                    Удалить
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {cropImage && (
                <ImageCropModal
                    isOpen={true}
                    onClose={() => setCropImage(null)}
                    imageUrl={cropImage.url}
                    onCropComplete={handleCropComplete}
                />
            )}
        </div>
    );
}

MediaLibraryIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Медиабиблиотека', href: mediaIndex() },
    ],
};

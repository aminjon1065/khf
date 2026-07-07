import { Head, router, useForm } from '@inertiajs/react';
import { Scissors, Trash2, UploadCloud } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import {
    MediaBrowserDetail,
    MediaBrowserFilters,
    MediaBrowserGrid,
    type MediaLibraryFilters,
    type MediaLibraryItem,
} from '@/components/admin/media-browser';
import { ImageCropModal } from '@/components/admin/image-crop-modal';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes/admin';
import {
    destroy as mediaDestroy,
    index as mediaIndex,
    store as mediaStore,
    update as mediaUpdate,
} from '@/routes/admin/media';

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
    filters: MediaLibraryFilters;
};

export default function MediaLibraryIndex({ mediaFiles, filters }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [selected, setSelected] = useState<MediaLibraryItem | null>(null);
    const [cropImage, setCropImage] = useState<{
        id: number;
        url: string;
    } | null>(null);

    const selectedItem = useMemo(() => {
        if (selected === null) {
            return null;
        }

        return mediaFiles.data.find((item) => item.id === selected.id) ?? selected;
    }, [mediaFiles.data, selected]);

    const detailForm = useForm({
        name: selectedItem?.name ?? '',
        alt_text: selectedItem?.alt_text ?? '',
    });

    const applyFilters = (next: Partial<MediaLibraryFilters>) => {
        router.get(
            mediaIndex().url,
            {
                search: next.search ?? filters.search,
                type: next.type ?? filters.type,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const handleUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
        if (!event.target.files || event.target.files.length === 0) {
            return;
        }

        const file = event.target.files[0];
        const formData = new FormData();
        formData.append('file', file);

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
                        Поиск, повторное использование файлов и alt-тексты
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
                <MediaBrowserFilters
                    search={filters.search}
                    type={filters.type}
                    onSearchChange={(search) => applyFilters({ search })}
                    onTypeChange={(type) => applyFilters({ type })}
                />
            </div>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
                    <MediaBrowserGrid
                        items={mediaFiles.data}
                        selectedId={selectedItem?.id ?? null}
                        onSelect={(item) => {
                            setSelected(item);
                            detailForm.setData({
                                name: item.name,
                                alt_text: item.alt_text ?? '',
                            });
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
                                <Label htmlFor="media-alt">Alt-текст</Label>
                                <Textarea
                                    id="media-alt"
                                    rows={3}
                                    value={detailForm.data.alt_text}
                                    onChange={(event) =>
                                        detailForm.setData(
                                            'alt_text',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Описание для доступности и SEO"
                                />
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

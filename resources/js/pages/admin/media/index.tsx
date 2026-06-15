import { Head, router } from '@inertiajs/react';
import { Scissors, Trash2, UploadCloud } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { ImageCropModal } from '@/components/admin/image-crop-modal';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes/admin';
import { index as mediaIndex, store as mediaStore, destroy as mediaDestroy } from '@/routes/admin/media';

type MediaItem = {
    id: number;
    original_url: string;
    name: string;
};

type MediaFile = {
    id: number;
    name: string;
    media: MediaItem[];
};

type Props = {
    mediaFiles: {
        data: MediaFile[];
        links: { url: string | null; label: string; active: boolean }[];
    };
};

export default function MediaLibraryIndex({ mediaFiles }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [cropImage, setCropImage] = useState<{ id: number; url: string } | null>(null);

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
        if (confirm('Удалить этот файл навсегда?')) {
            router.delete(mediaDestroy(id).url, {
                preserveScroll: true,
                onSuccess: () => toast.success('Файл удалён'),
            });
        }
    };

    const handleCropComplete = (blob: Blob) => {
        const formData = new FormData();
        formData.append('file', new File([blob], 'cropped-image.jpg', { type: 'image/jpeg' }));

        router.post(mediaStore().url, formData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Обрезанное изображение сохранено');
                setCropImage(null);
            },
            onError: () => toast.error('Ошибка при сохранении'),
        });
    };

    return (
        <div className="p-4 sm:p-6">
            <Head title="Медиабиблиотека" />

            <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Медиабиблиотека</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Управление медиафайлами (изображения, документы)
                    </p>
                </div>
                <Button onClick={() => fileInputRef.current?.click()} disabled={uploading}>
                    <UploadCloud className="size-4" />
                    {uploading ? 'Загрузка…' : 'Добавить файл'}
                </Button>
                <input type="file" className="hidden" ref={fileInputRef} onChange={handleUpload} />
            </div>

            <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
                {mediaFiles.data.length === 0 ? (
                    <div className="py-16 text-center text-muted-foreground">
                        Файлов нет. Загрузите первый файл.
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        {mediaFiles.data.map((file) => {
                            const mediaItem = file.media?.[0];

                            if (!mediaItem) {
                                return null;
                            }

                            const isImage =
                                mediaItem.original_url.match(/\.(jpeg|jpg|gif|png|webp|avif)$/i) != null;

                            return (
                                <div
                                    key={file.id}
                                    className="group relative aspect-square overflow-hidden rounded-lg border border-border bg-muted"
                                >
                                    {isImage ? (
                                        <img
                                            src={mediaItem.original_url}
                                            alt={file.name}
                                            className="size-full object-cover transition-transform group-hover:scale-105"
                                        />
                                    ) : (
                                        <div className="flex size-full flex-col items-center justify-center break-all p-2 text-center text-sm text-muted-foreground">
                                            <div className="mb-2 font-bold">DOC</div>
                                            {file.name}
                                        </div>
                                    )}

                                    <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                                        {isImage && (
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                className="w-24"
                                                onClick={() => setCropImage({ id: file.id, url: mediaItem.original_url })}
                                            >
                                                <Scissors className="size-4" />
                                                Кроп
                                            </Button>
                                        )}
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            className="w-24"
                                            onClick={() => handleDelete(file.id)}
                                        >
                                            <Trash2 className="size-4" />
                                            Удалить
                                        </Button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
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

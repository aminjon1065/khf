import { Head, router } from '@inertiajs/react';
import { AppShell } from '@/components/app-shell';
import { Breadcrumbs } from '@/components/breadcrumbs';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { UploadCloud, Trash2, Scissors } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { ImageCropModal } from '@/components/admin/image-crop-modal';

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
        links: any[];
    };
};

export default function MediaLibraryIndex({ mediaFiles }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    
    const [cropImage, setCropImage] = useState<{ id: number; url: string } | null>(null);

    const handleUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (!e.target.files || e.target.files.length === 0) return;
        const file = e.target.files[0];

        setUploading(true);
        const formData = new FormData();
        formData.append('file', file);

        router.post('/admin/media', formData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Файл загружен');
                if (fileInputRef.current) fileInputRef.current.value = '';
            },
            onError: () => toast.error('Ошибка при загрузке файла'),
            onFinish: () => setUploading(false),
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Удалить этот файл навсегда?')) {
            router.delete(`/admin/media/${id}`, {
                preserveScroll: true,
                onSuccess: () => toast.success('Файл удален'),
            });
        }
    };

    const handleCropComplete = (blob: Blob) => {
        const formData = new FormData();
        const file = new File([blob], 'cropped-image.jpg', { type: 'image/jpeg' });
        formData.append('file', file);

        router.post('/admin/media', formData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Обрезанное изображение сохранено');
                setCropImage(null);
            },
            onError: () => toast.error('Ошибка при сохранении'),
        });
    };

    return (
        <AppShell>
            <Head title="Медиабиблиотека" />
            <Breadcrumbs
                items={[
                    { title: 'Медиабиблиотека', url: '/admin/media' },
                ]}
            />

            <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between py-4">
                <Heading
                    title="Медиабиблиотека"
                    description="Управление всеми медиафайлами (изображения, документы)"
                />
                <Button onClick={() => fileInputRef.current?.click()} disabled={uploading}>
                    <UploadCloud className="mr-2 h-4 w-4" />
                    {uploading ? 'Загрузка...' : 'Добавить файл'}
                </Button>
                <input
                    type="file"
                    className="hidden"
                    ref={fileInputRef}
                    onChange={handleUpload}
                />
            </div>

            <Card>
                <CardContent className="p-6">
                    {mediaFiles.data.length === 0 ? (
                        <div className="text-center py-12 text-muted-foreground">
                            Файлов нет. Загрузите первый файл.
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            {mediaFiles.data.map((file) => {
                                const mediaItem = file.media?.[0];
                                if (!mediaItem) return null;

                                const isImage = mediaItem.original_url.match(/\.(jpeg|jpg|gif|png|webp|avif)$/i) != null;

                                return (
                                    <div key={file.id} className="relative group border rounded-lg overflow-hidden aspect-square bg-muted">
                                        {isImage ? (
                                            <img
                                                src={mediaItem.original_url}
                                                alt={file.name}
                                                className="w-full h-full object-cover transition-transform group-hover:scale-105"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex flex-col items-center justify-center text-muted-foreground p-2 text-center text-sm break-all">
                                                <div className="font-bold mb-2">DOC</div>
                                                {file.name}
                                            </div>
                                        )}
                                        
                                        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2">
                                            {isImage && (
                                                <Button 
                                                    variant="secondary" 
                                                    size="sm" 
                                                    className="w-24"
                                                    onClick={() => setCropImage({ id: file.id, url: mediaItem.original_url })}
                                                >
                                                    <Scissors className="h-4 w-4 mr-2" />
                                                    Кроп
                                                </Button>
                                            )}
                                            <Button 
                                                variant="destructive" 
                                                size="sm" 
                                                className="w-24"
                                                onClick={() => handleDelete(file.id)}
                                            >
                                                <Trash2 className="h-4 w-4 mr-2" />
                                                Удалить
                                            </Button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </CardContent>
            </Card>

            {cropImage && (
                <ImageCropModal
                    isOpen={true}
                    onClose={() => setCropImage(null)}
                    imageUrl={cropImage.url}
                    onCropComplete={handleCropComplete}
                />
            )}
        </AppShell>
    );
}

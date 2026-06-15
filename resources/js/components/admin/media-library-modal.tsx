import { Loader2, UploadCloud, Check } from 'lucide-react';
import { useEffect, useState, useRef } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';

type MediaFile = {
    id: number;
    name: string;
    media: {
        id: number;
        original_url: string;
        name: string;
    }[];
};

type MediaLibraryModalProps = {
    isOpen: boolean;
    onClose: () => void;
    onSelect: (url: string) => void;
};

export function MediaLibraryModal({ isOpen, onClose, onSelect }: MediaLibraryModalProps) {
    const [mediaFiles, setMediaFiles] = useState<MediaFile[]>([]);
    const [loading, setLoading] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        // Intentional: show the spinner immediately while the library loads on open.
        // eslint-disable-next-line react-hooks/set-state-in-effect
        setLoading(true);

        fetch('/admin/api/media')
            .then((res) => res.json())
            .then((data) => setMediaFiles(data.data || []))
            .catch((err) => console.error('Failed to fetch media', err))
            .finally(() => setLoading(false));
    }, [isOpen]);

    const handleUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        if (!e.target.files || e.target.files.length === 0) {
return;
}

        const file = e.target.files[0];

        const formData = new FormData();
        formData.append('file', file);

        setUploading(true);

        try {
            const res = await fetch('/admin/media', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: formData,
            });
            const newMedia = await res.json();
            setMediaFiles((prev) => [newMedia, ...prev]);
        } catch (err) {
            console.error('Upload failed', err);
        } finally {
            setUploading(false);

            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    };

    const handleInsert = () => {
        if (selectedId) {
            const file = mediaFiles.find(m => m.id === selectedId);

            if (file && file.media && file.media.length > 0) {
                onSelect(file.media[0].original_url);
            }

            onClose();
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-4xl h-[80vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Медиабиблиотека</DialogTitle>
                </DialogHeader>

                <div className="flex items-center justify-between py-2 border-b">
                    <div>
                        <Button variant="outline" onClick={() => fileInputRef.current?.click()} disabled={uploading}>
                            {uploading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <UploadCloud className="mr-2 h-4 w-4" />}
                            Загрузить файл
                        </Button>
                        <input
                            type="file"
                            className="hidden"
                            ref={fileInputRef}
                            onChange={handleUpload}
                            accept="image/*,application/pdf"
                        />
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto py-4">
                    {loading ? (
                        <div className="flex items-center justify-center h-full">
                            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                        </div>
                    ) : mediaFiles.length === 0 ? (
                        <div className="flex items-center justify-center h-full text-muted-foreground">
                            Медиафайлы не найдены
                        </div>
                    ) : (
                        <div className="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-4">
                            {mediaFiles.map((file) => {
                                const isSelected = selectedId === file.id;
                                const mediaItem = file.media?.[0];

                                if (!mediaItem) {
return null;
}

                                const isImage = mediaItem.original_url.match(/\.(jpeg|jpg|gif|png|webp|avif)$/i) != null;

                                return (
                                    <div
                                        key={file.id}
                                        onClick={() => setSelectedId(file.id)}
                                        className={`relative cursor-pointer border-2 rounded-lg overflow-hidden aspect-square ${
                                            isSelected ? 'border-primary ring-2 ring-primary ring-offset-2' : 'border-transparent hover:border-border'
                                        }`}
                                    >
                                        {isImage ? (
                                            <img
                                                src={mediaItem.original_url}
                                                alt={file.name}
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex flex-col items-center justify-center bg-muted text-muted-foreground p-2 text-center text-xs break-all">
                                                <div className="font-bold mb-1 text-sm">DOC</div>
                                                {file.name}
                                            </div>
                                        )}
                                        {isSelected && (
                                            <div className="absolute top-1 right-1 bg-primary text-primary-foreground rounded-full p-0.5">
                                                <Check className="h-4 w-4" />
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Отмена
                    </Button>
                    <Button onClick={handleInsert} disabled={!selectedId}>
                        Вставить
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

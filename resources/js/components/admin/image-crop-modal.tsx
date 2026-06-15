import { Loader2 } from 'lucide-react';
import { useRef, useState } from 'react';
import ReactCrop from 'react-image-crop';
import type { Crop, PixelCrop } from 'react-image-crop';
import 'react-image-crop/dist/ReactCrop.css';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';

type ImageCropModalProps = {
    isOpen: boolean;
    onClose: () => void;
    imageUrl: string;
    onCropComplete: (croppedBlob: Blob) => void;
};

export function ImageCropModal({ isOpen, onClose, imageUrl, onCropComplete }: ImageCropModalProps) {
    const [crop, setCrop] = useState<Crop>({
        unit: '%',
        x: 10,
        y: 10,
        width: 80,
        height: 80,
    });
    const [completedCrop, setCompletedCrop] = useState<PixelCrop | null>(null);
    const imgRef = useRef<HTMLImageElement>(null);
    const [isCropping, setIsCropping] = useState(false);

    const getCroppedImg = async (image: HTMLImageElement, crop: PixelCrop): Promise<Blob> => {
        const canvas = document.createElement('canvas');
        const scaleX = image.naturalWidth / image.width;
        const scaleY = image.naturalHeight / image.height;
        canvas.width = crop.width;
        canvas.height = crop.height;
        const ctx = canvas.getContext('2d');

        if (!ctx) {
            throw new Error('No 2d context');
        }

        ctx.drawImage(
            image,
            crop.x * scaleX,
            crop.y * scaleY,
            crop.width * scaleX,
            crop.height * scaleY,
            0,
            0,
            crop.width,
            crop.height
        );

        return new Promise((resolve, reject) => {
            canvas.toBlob(
                (blob) => {
                    if (!blob) {
                        reject(new Error('Canvas is empty'));

                        return;
                    }

                    resolve(blob);
                },
                'image/jpeg',
                0.95
            );
        });
    };

    const handleSave = async () => {
        if (!completedCrop || !imgRef.current) {
return;
}

        setIsCropping(true);

        try {
            const blob = await getCroppedImg(imgRef.current, completedCrop);
            onCropComplete(blob);
        } catch (e) {
            console.error(e);
            alert('Ошибка обрезки изображения');
        } finally {
            setIsCropping(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-3xl max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Обрезать изображение</DialogTitle>
                </DialogHeader>

                <div className="flex-1 overflow-auto bg-muted/50 flex items-center justify-center p-4 rounded-md">
                    {imageUrl ? (
                        <ReactCrop
                            crop={crop}
                            onChange={(_, percentCrop) => setCrop(percentCrop)}
                            onComplete={(c) => setCompletedCrop(c)}
                        >
                            <img
                                ref={imgRef}
                                alt="Crop me"
                                src={imageUrl}
                                className="max-w-full max-h-[60vh] object-contain"
                                crossOrigin="anonymous" // needed if images are on different domains/CORS
                            />
                        </ReactCrop>
                    ) : (
                        <div className="text-muted-foreground">Загрузка...</div>
                    )}
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={isCropping}>
                        Отмена
                    </Button>
                    <Button onClick={handleSave} disabled={!completedCrop || isCropping}>
                        {isCropping && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Сохранить копию
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

import { X } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type ExistingPhoto = {
    id: number;
    url: string;
    name: string;
};

export function CpPhotoGalleryField({
    id,
    label,
    instructions,
    accept,
    existingPhotos,
    pendingPhotos,
    removeIds,
    onAddPhotos,
    onRemoveExisting,
    error,
}: {
    id: string;
    label: string;
    instructions?: string;
    accept?: string;
    existingPhotos: ExistingPhoto[];
    pendingPhotos: File[];
    removeIds: number[];
    onAddPhotos: (files: File[]) => void;
    onRemoveExisting: (mediaId: number) => void;
    error?: string;
}) {
    const visiblePhotos = existingPhotos.filter(
        (photo) => !removeIds.includes(photo.id),
    );

    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            {instructions ? (
                <p className="text-xs text-muted-foreground">{instructions}</p>
            ) : null}

            {visiblePhotos.length > 0 && (
                <div className="grid grid-cols-3 gap-3 sm:grid-cols-4">
                    {visiblePhotos.map((photo) => (
                        <div
                            key={photo.id}
                            className="group relative overflow-hidden rounded-md border"
                        >
                            <img
                                src={photo.url}
                                alt={photo.name}
                                className="aspect-square w-full object-cover"
                            />
                            <Button
                                type="button"
                                variant="destructive"
                                size="icon"
                                aria-label="Убрать фото"
                                className="absolute top-1 right-1 size-6"
                                onClick={() => onRemoveExisting(photo.id)}
                            >
                                <X className="size-3.5" />
                            </Button>
                        </div>
                    ))}
                </div>
            )}

            {pendingPhotos.length > 0 && (
                <ul className="space-y-1 text-xs text-muted-foreground">
                    {pendingPhotos.map((file, index) => (
                        <li key={`${file.name}-${index}`}>{file.name}</li>
                    ))}
                </ul>
            )}

            <Input
                id={id}
                type="file"
                multiple
                accept={accept}
                onChange={(event) =>
                    onAddPhotos(Array.from(event.target.files ?? []))
                }
            />
            <InputError message={error} />
        </div>
    );
}

import { FileText, X } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type ExistingAssetFile = {
    id: number;
    name: string;
    size: string;
    url: string;
};

export function CpMultiAssetsField({
    id,
    label,
    instructions,
    accept,
    existingFiles,
    pendingFiles,
    removeIds,
    onAddFiles,
    onRemoveExisting,
    error,
}: {
    id: string;
    label: string;
    instructions?: string;
    accept?: string;
    existingFiles: ExistingAssetFile[];
    pendingFiles: File[];
    removeIds: number[];
    onAddFiles: (files: File[]) => void;
    onRemoveExisting: (mediaId: number) => void;
    error?: string;
}) {
    const visibleExisting = existingFiles.filter(
        (file) => !removeIds.includes(file.id),
    );

    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            {instructions ? (
                <p className="text-xs text-muted-foreground">{instructions}</p>
            ) : null}

            {visibleExisting.length > 0 && (
                <ul className="space-y-2">
                    {visibleExisting.map((file) => (
                        <li
                            key={file.id}
                            className="flex items-center gap-3 rounded-md border border-border p-2 text-sm"
                        >
                            <FileText className="size-4 text-muted-foreground" />
                            <a
                                href={file.url}
                                className="min-w-0 flex-1 truncate text-primary hover:underline"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {file.name}
                            </a>
                            <span className="text-muted-foreground">
                                {file.size}
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label="Убрать файл"
                                onClick={() => onRemoveExisting(file.id)}
                            >
                                <X className="size-4" />
                            </Button>
                        </li>
                    ))}
                </ul>
            )}

            {pendingFiles.length > 0 && (
                <ul className="space-y-1 text-xs text-muted-foreground">
                    {pendingFiles.map((file, index) => (
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
                    onAddFiles(Array.from(event.target.files ?? []))
                }
            />
            <InputError message={error} />
        </div>
    );
}

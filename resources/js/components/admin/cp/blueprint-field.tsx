import { CpAssetsField } from '@/components/admin/cp/assets-field';
import { CpBlocksField } from '@/components/admin/cp/blocks-field';
import type { BlockData } from '@/components/admin/cp/blocks-field';
import { CpContentPublishPanel } from '@/components/admin/cp/content-publish-panel';
import {
    CpRichTextField,
    CpSelectField,
    CpTextareaField,
    CpTextField,
    CpToggleField,
} from '@/components/admin/cp/fields';
import { CpMultiAssetsField } from '@/components/admin/cp/multi-assets-field';
import { CpMultiRelationField } from '@/components/admin/cp/multi-relation-field';
import { CpNestedRowsField } from '@/components/admin/cp/nested-rows-field';
import { CpPhotoGalleryField } from '@/components/admin/cp/photo-gallery-field';
import { CpRelationField } from '@/components/admin/cp/relation-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    BlueprintFieldDefinition,
    BlueprintFieldOptions,
    BlueprintFormMeta,
    BlockSetDefinition,
    RelationOption,
    SelectOption,
} from '@/types/cms';

type FieldValue =
    | string
    | number
    | boolean
    | number[]
    | BlockData[]
    | Record<string, string>[]
    | null;

function relationOptions(
    fieldOptions: BlueprintFieldOptions,
    handle: string,
): RelationOption[] {
    const options = fieldOptions[handle];

    return Array.isArray(options) ? (options as RelationOption[]) : [];
}

function selectOptions(
    fieldOptions: BlueprintFieldOptions,
    handle: string,
): SelectOption[] {
    const options = fieldOptions[handle];

    return Array.isArray(options) ? (options as SelectOption[]) : [];
}

function fieldError(
    errors: Record<string, string | undefined>,
    handle: string,
    activeLocale: string,
    localizable: boolean,
): string | undefined {
    if (localizable) {
        return errors[`translations.${activeLocale}.${handle}`];
    }

    return errors[handle];
}

export function CpBlueprintField({
    field,
    value,
    onChange,
    errors,
    activeLocale,
    fieldOptions,
    meta,
    editorKey,
    data,
    onRootChange,
    onAssetChange,
    blockset,
}: {
    field: BlueprintFieldDefinition;
    value: FieldValue;
    onChange: (value: FieldValue) => void;
    errors: Record<string, string | undefined>;
    activeLocale: string;
    fieldOptions: BlueprintFieldOptions;
    meta: BlueprintFormMeta;
    editorKey?: string;
    data: Record<string, unknown>;
    onRootChange: (handle: string, value: unknown) => void;
    onAssetChange: (patch: Record<string, unknown>) => void;
    blockset?: BlockSetDefinition;
}) {
    const error = fieldError(
        errors,
        field.handle,
        activeLocale,
        field.localizable,
    );
    const id = `${field.handle}-${activeLocale}`;

    if (field.type === 'status') {
        return (
            <CpContentPublishPanel
                status={String(data.status ?? 'draft')}
                statuses={meta.statuses}
                transitions={meta.statusTransitions}
                publishedAt={String(data.published_at ?? '')}
                unpublishedAt={String(data.unpublished_at ?? '')}
                showSchedule={meta.showSchedule}
                onStatusChange={(status) => onRootChange('status', status)}
                onPublishedAtChange={(publishedAt) =>
                    onRootChange('published_at', publishedAt)
                }
                onUnpublishedAtChange={(unpublishedAt) =>
                    onRootChange('unpublished_at', unpublishedAt)
                }
                errors={errors}
            />
        );
    }

    if (field.type === 'assets') {
        if (field.handle === 'files') {
            const existingFiles = meta.existingFiles ?? [];
            const pendingFiles = (data.files as File[] | undefined) ?? [];
            const removeIds = (data.remove_files as number[] | undefined) ?? [];

            return (
                <CpMultiAssetsField
                    id={id}
                    label={field.display}
                    instructions={field.instructions ?? undefined}
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.jpg,.jpeg,.png"
                    existingFiles={existingFiles}
                    pendingFiles={pendingFiles}
                    removeIds={removeIds}
                    onAddFiles={(files) => onRootChange('files', files)}
                    onRemoveExisting={(mediaId) =>
                        onRootChange('remove_files', [...removeIds, mediaId])
                    }
                    error={errors['files.0'] ?? errors.files}
                />
            );
        }

        if (field.handle === 'photos') {
            const existingPhotos = meta.existingPhotos ?? [];
            const pendingPhotos = (data.photos as File[] | undefined) ?? [];
            const removeIds =
                (data.remove_photos as number[] | undefined) ?? [];

            return (
                <CpPhotoGalleryField
                    id={id}
                    label={field.display}
                    instructions={field.instructions ?? undefined}
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    existingPhotos={existingPhotos}
                    pendingPhotos={pendingPhotos}
                    removeIds={removeIds}
                    onAddPhotos={(files) => onRootChange('photos', files)}
                    onRemoveExisting={(mediaId) =>
                        onRootChange('remove_photos', [...removeIds, mediaId])
                    }
                    error={errors['photos.0'] ?? errors.photos}
                />
            );
        }

        if (field.handle === 'photo') {
            return (
                <CpAssetsField
                    label={field.display}
                    instructions={field.instructions ?? undefined}
                    currentUrl={meta.photoUrl ?? null}
                    file={(data.photo as File | null) ?? null}
                    mediaId={null}
                    removed={Boolean(data.remove_photo)}
                    onUpload={(file) =>
                        onAssetChange({
                            photo: file,
                            remove_photo: false,
                        })
                    }
                    onPickAsset={() => {}}
                    onClear={() =>
                        onAssetChange({
                            photo: null,
                            remove_photo: true,
                        })
                    }
                    error={errors.photo}
                />
            );
        }

        return (
            <CpAssetsField
                label={field.display}
                instructions={field.instructions ?? undefined}
                currentUrl={meta.coverUrl ?? null}
                file={(data.cover as File | null) ?? null}
                mediaId={(data.cover_media_id as number | null) ?? null}
                removed={Boolean(data.remove_cover)}
                onUpload={(file) =>
                    onAssetChange({
                        cover: file,
                        cover_media_id: null,
                        remove_cover: false,
                    })
                }
                onPickAsset={(asset) =>
                    onAssetChange({
                        cover: null,
                        cover_media_id: asset?.id ?? null,
                        remove_cover: false,
                    })
                }
                onClear={() =>
                    onAssetChange({
                        cover: null,
                        cover_media_id: null,
                        remove_cover: true,
                    })
                }
                error={errors.cover ?? errors.cover_media_id}
            />
        );
    }

    if (field.type === 'entries') {
        const options = relationOptions(fieldOptions, field.handle);

        if (field.max === 1) {
            return (
                <CpRelationField
                    id={id}
                    label={field.display}
                    instructions={field.instructions ?? undefined}
                    value={typeof value === 'number' ? value : null}
                    options={options}
                    onChange={(next) => onChange(next)}
                    placeholder="— Нет —"
                    error={error}
                />
            );
        }

        return (
            <CpMultiRelationField
                id={id}
                label={field.display}
                instructions={field.instructions ?? undefined}
                value={Array.isArray(value) ? (value as number[]) : []}
                options={options}
                onChange={(next) => onChange(next)}
                placeholder="— Нет —"
                error={error}
            />
        );
    }

    if (field.type === 'select') {
        const options = selectOptions(fieldOptions, field.handle);

        return (
            <CpSelectField
                id={id}
                label={field.display}
                instructions={field.instructions ?? undefined}
                value={String(value ?? '')}
                onChange={(next) => onChange(next)}
                options={options}
                error={error}
            />
        );
    }

    if (field.type === 'toggle') {
        return (
            <CpToggleField
                id={id}
                label={field.display}
                instructions={field.instructions ?? undefined}
                checked={Boolean(value)}
                onChange={(checked) => onChange(checked)}
            />
        );
    }

    if (field.type === 'date') {
        const inputType = [
            'published_at',
            'unpublished_at',
            'starts_at',
            'ends_at',
            'deadline_at',
            'occurred_at',
        ].includes(field.handle)
            ? 'datetime-local'
            : 'date';

        return (
            <div className="space-y-2">
                <Label htmlFor={id}>{field.display}</Label>
                <Input
                    id={id}
                    type={inputType}
                    value={String(value ?? '')}
                    onChange={(event) => onChange(event.target.value)}
                />
            </div>
        );
    }

    if (field.type === 'number') {
        return (
            <CpTextField
                id={id}
                label={field.display}
                instructions={field.instructions ?? undefined}
                type="number"
                value={String(value ?? 0)}
                onChange={(next) => onChange(Number(next))}
                error={error}
            />
        );
    }

    if (field.type === 'textarea') {
        return (
            <CpTextareaField
                id={id}
                label={field.display}
                instructions={field.instructions ?? undefined}
                rows={field.rows}
                value={String(value ?? '')}
                onChange={(next) => onChange(next)}
                error={error}
            />
        );
    }

    if (field.type === 'rich_text' || field.type === 'bard') {
        return (
            <CpRichTextField
                id={id}
                label={field.display}
                instructions={field.instructions ?? undefined}
                editorKey={editorKey}
                value={String(value ?? '')}
                onChange={(html) => onChange(html)}
                error={error}
            />
        );
    }

    if (field.type === 'blocks') {
        return (
            <CpBlocksField
                editorKey={editorKey}
                value={Array.isArray(value) ? (value as BlockData[]) : []}
                onChange={(blocks) => onChange(blocks)}
                blockTypes={blockset?.blocks}
            />
        );
    }

    if (field.type === 'grid' || field.type === 'replicator') {
        const subFields = field.sub_fields ?? [];

        return (
            <CpNestedRowsField
                id={id}
                label={field.display}
                instructions={field.instructions ?? undefined}
                mode={field.type === 'grid' ? 'grid' : 'replicator'}
                subFields={subFields}
                value={
                    Array.isArray(value)
                        ? (value as unknown as Record<string, string>[])
                        : []
                }
                onChange={(rows) => onChange(rows)}
                max={field.max}
                error={error}
            />
        );
    }

    return (
        <CpTextField
            id={id}
            label={field.display}
            instructions={field.instructions ?? undefined}
            value={String(value ?? '')}
            onChange={(next) => onChange(next)}
            error={error}
        />
    );
}

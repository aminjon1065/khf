import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ChevronDown, ChevronUp, GripVertical, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { fieldTypeLabel, BLUEPRINT_FIELD_TYPES } from '@/lib/blueprint-builder';
import type { BuilderField } from '@/lib/blueprint-builder';

export function BlueprintFieldRow({
    field,
    onChange,
    onRemove,
}: {
    field: BuilderField;
    onChange: (patch: Partial<BuilderField>) => void;
    onRemove: () => void;
}) {
    const [expanded, setExpanded] = useState(false);
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: field.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.65 : 1,
    };

    const hasNested = field.type === 'grid' || field.type === 'replicator';

    return (
        <Card ref={setNodeRef} style={style} className="overflow-hidden">
            <CardHeader className="flex flex-row items-center justify-between gap-3 space-y-0 bg-muted/40 py-3">
                <div className="flex min-w-0 items-center gap-2">
                    <button
                        type="button"
                        className="cursor-grab touch-none text-muted-foreground active:cursor-grabbing"
                        aria-label="Перетащить поле"
                        {...attributes}
                        {...listeners}
                    >
                        <GripVertical className="size-4" />
                    </button>
                    <div className="min-w-0">
                        <CardTitle className="truncate text-sm font-medium">
                            {field.display || field.handle}
                        </CardTitle>
                        <p className="truncate font-mono text-xs text-muted-foreground">
                            {field.handle} · {fieldTypeLabel(field.type)}
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        onClick={() => setExpanded((value) => !value)}
                        aria-label={
                            expanded ? 'Свернуть поле' : 'Развернуть поле'
                        }
                    >
                        {expanded ? (
                            <ChevronUp className="size-4" />
                        ) : (
                            <ChevronDown className="size-4" />
                        )}
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-8 text-destructive"
                        onClick={onRemove}
                        aria-label="Удалить поле"
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            </CardHeader>

            {expanded ? (
                <CardContent className="grid gap-4 p-4 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor={`${field.id}-handle`}>Handle</Label>
                        <Input
                            id={`${field.id}-handle`}
                            value={field.handle}
                            onChange={(event) =>
                                onChange({
                                    handle: event.target.value
                                        .toLowerCase()
                                        .replace(/[^a-z0-9_]/g, '_'),
                                })
                            }
                            className="font-mono text-sm"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor={`${field.id}-type`}>Тип</Label>
                        <Select
                            value={field.type}
                            onValueChange={(value) => onChange({ type: value })}
                        >
                            <SelectTrigger id={`${field.id}-type`}>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {BLUEPRINT_FIELD_TYPES.map((item) => (
                                    <SelectItem
                                        key={item.type}
                                        value={item.type}
                                    >
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor={`${field.id}-display`}>Название</Label>
                        <Input
                            id={`${field.id}-display`}
                            value={field.display}
                            onChange={(event) =>
                                onChange({ display: event.target.value })
                            }
                        />
                    </div>

                    <div className="space-y-2 sm:col-span-2">
                        <Label htmlFor={`${field.id}-instructions`}>
                            Подсказка
                        </Label>
                        <Textarea
                            id={`${field.id}-instructions`}
                            value={field.instructions ?? ''}
                            onChange={(event) =>
                                onChange({
                                    instructions: event.target.value || null,
                                })
                            }
                            rows={2}
                        />
                    </div>

                    {field.type === 'entries' ? (
                        <div className="space-y-2">
                            <Label htmlFor={`${field.id}-collection`}>
                                Коллекция
                            </Label>
                            <Input
                                id={`${field.id}-collection`}
                                value={field.collection ?? ''}
                                onChange={(event) =>
                                    onChange({
                                        collection: event.target.value || null,
                                    })
                                }
                                placeholder="categories, tags, pages"
                            />
                        </div>
                    ) : null}

                    {(field.type === 'entries' ||
                        field.type === 'grid' ||
                        field.type === 'replicator') && (
                        <div className="space-y-2">
                            <Label htmlFor={`${field.id}-max`}>Max</Label>
                            <Input
                                id={`${field.id}-max`}
                                type="number"
                                min={1}
                                value={field.max ?? ''}
                                onChange={(event) =>
                                    onChange({
                                        max: event.target.value
                                            ? Number(event.target.value)
                                            : null,
                                    })
                                }
                            />
                        </div>
                    )}

                    {field.type === 'textarea' ? (
                        <div className="space-y-2">
                            <Label htmlFor={`${field.id}-rows`}>Строк</Label>
                            <Input
                                id={`${field.id}-rows`}
                                type="number"
                                min={1}
                                value={field.rows ?? 4}
                                onChange={(event) =>
                                    onChange({
                                        rows: Number(event.target.value) || 4,
                                    })
                                }
                            />
                        </div>
                    ) : null}

                    <div className="flex flex-wrap gap-4 sm:col-span-2">
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={field.localizable}
                                onCheckedChange={(checked) =>
                                    onChange({ localizable: checked === true })
                                }
                            />
                            Локализуемое
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={field.required}
                                onCheckedChange={(checked) =>
                                    onChange({ required: checked === true })
                                }
                            />
                            Обязательное
                        </label>
                    </div>

                    {hasNested ? (
                        <div className="space-y-3 sm:col-span-2">
                            <Label>Вложенные поля (YAML keys)</Label>
                            {(field.sub_fields ?? []).map((subField, index) => (
                                <div
                                    key={`${field.id}-sub-${index}`}
                                    className="grid gap-3 rounded-md border p-3 sm:grid-cols-3"
                                >
                                    <Input
                                        value={subField.handle}
                                        onChange={(event) => {
                                            const subFields = [
                                                ...(field.sub_fields ?? []),
                                            ];
                                            subFields[index] = {
                                                ...subField,
                                                handle: event.target.value
                                                    .toLowerCase()
                                                    .replace(
                                                        /[^a-z0-9_]/g,
                                                        '_',
                                                    ),
                                            };
                                            onChange({ sub_fields: subFields });
                                        }}
                                        placeholder="handle"
                                        className="font-mono text-sm"
                                    />
                                    <Input
                                        value={subField.display}
                                        onChange={(event) => {
                                            const subFields = [
                                                ...(field.sub_fields ?? []),
                                            ];
                                            subFields[index] = {
                                                ...subField,
                                                display: event.target.value,
                                            };
                                            onChange({ sub_fields: subFields });
                                        }}
                                        placeholder="Название"
                                    />
                                    <Select
                                        value={subField.type}
                                        onValueChange={(value) => {
                                            const subFields = [
                                                ...(field.sub_fields ?? []),
                                            ];
                                            subFields[index] = {
                                                ...subField,
                                                type: value,
                                            };
                                            onChange({ sub_fields: subFields });
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="text">
                                                Текст
                                            </SelectItem>
                                            <SelectItem value="textarea">
                                                Textarea
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            ))}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    onChange({
                                        sub_fields: [
                                            ...(field.sub_fields ?? []),
                                            {
                                                handle: `item_${(field.sub_fields?.length ?? 0) + 1}`,
                                                type: 'text',
                                                display: 'Поле',
                                            },
                                        ],
                                    })
                                }
                            >
                                Добавить вложенное поле
                            </Button>
                        </div>
                    ) : null}
                </CardContent>
            ) : null}
        </Card>
    );
}

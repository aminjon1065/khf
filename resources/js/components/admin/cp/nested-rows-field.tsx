import { Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { BlueprintSubFieldDefinition } from '@/types/cms';

type RowValue = Record<string, string>;

function emptyRow(subFields: BlueprintSubFieldDefinition[]): RowValue {
    return Object.fromEntries(subFields.map((field) => [field.handle, '']));
}

export function CpNestedRowsField({
    id,
    label,
    instructions,
    mode,
    subFields,
    value,
    onChange,
    max,
    error,
}: {
    id: string;
    label: string;
    instructions?: string;
    mode: 'grid' | 'replicator';
    subFields: BlueprintSubFieldDefinition[];
    value: RowValue[];
    onChange: (rows: RowValue[]) => void;
    max?: number | null;
    error?: string;
}) {
    const rows = Array.isArray(value) ? value : [];
    const canAdd = max == null || rows.length < max;

    const updateRow = (index: number, handle: string, next: string) => {
        const nextRows = rows.map((row, rowIndex) =>
            rowIndex === index ? { ...row, [handle]: next } : row,
        );
        onChange(nextRows);
    };

    const addRow = () => {
        if (!canAdd) {
            return;
        }

        onChange([...rows, emptyRow(subFields)]);
    };

    const removeRow = (index: number) => {
        onChange(rows.filter((_, rowIndex) => rowIndex !== index));
    };

    const renderSubField = (
        rowIndex: number,
        field: BlueprintSubFieldDefinition,
        row: RowValue,
    ) => {
        const fieldId = `${id}-${rowIndex}-${field.handle}`;
        const fieldValue = row[field.handle] ?? '';

        if (field.type === 'textarea') {
            return (
                <Textarea
                    id={fieldId}
                    value={fieldValue}
                    onChange={(event) =>
                        updateRow(rowIndex, field.handle, event.target.value)
                    }
                    rows={field.rows ?? 2}
                />
            );
        }

        return (
            <Input
                id={fieldId}
                value={fieldValue}
                onChange={(event) =>
                    updateRow(rowIndex, field.handle, event.target.value)
                }
            />
        );
    };

    return (
        <div className="space-y-3">
            <div className="space-y-1">
                <Label htmlFor={id}>{label}</Label>
                {instructions ? (
                    <p className="text-xs text-muted-foreground">
                        {instructions}
                    </p>
                ) : null}
            </div>

            {mode === 'grid' && subFields.length > 0 ? (
                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-left">
                            <tr>
                                {subFields.map((field) => (
                                    <th
                                        key={field.handle}
                                        className="px-3 py-2 font-medium"
                                    >
                                        {field.display}
                                    </th>
                                ))}
                                <th className="w-10 px-2" />
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, rowIndex) => (
                                <tr key={rowIndex} className="border-t">
                                    {subFields.map((field) => (
                                        <td
                                            key={field.handle}
                                            className="px-3 py-2 align-top"
                                        >
                                            {renderSubField(
                                                rowIndex,
                                                field,
                                                row,
                                            )}
                                        </td>
                                    ))}
                                    <td className="px-2 py-2 align-top">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => removeRow(rowIndex)}
                                            aria-label="Удалить строку"
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="space-y-3">
                    {rows.map((row, rowIndex) => (
                        <div
                            key={rowIndex}
                            className="space-y-3 rounded-lg border bg-card p-4"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    #{rowIndex + 1}
                                </span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => removeRow(rowIndex)}
                                    aria-label="Удалить блок"
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2">
                                {subFields.map((field) => (
                                    <div
                                        key={field.handle}
                                        className="space-y-1.5"
                                    >
                                        <Label
                                            htmlFor={`${id}-${rowIndex}-${field.handle}`}
                                        >
                                            {field.display}
                                        </Label>
                                        {renderSubField(rowIndex, field, row)}
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {rows.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Пока нет строк. Добавьте первую.
                </p>
            ) : null}

            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={addRow}
                disabled={!canAdd}
            >
                <Plus className="size-4" />
                Добавить
            </Button>

            {error ? <p className="text-xs text-destructive">{error}</p> : null}
        </div>
    );
}

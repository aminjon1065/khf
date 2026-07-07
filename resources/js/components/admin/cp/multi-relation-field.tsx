import { Check, ChevronRight, Search, X } from 'lucide-react';
import { useState } from 'react';
import { CpStack } from '@/components/admin/cp/stack';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type Option = { id: number; name: string };

/**
 * Multi-select relationship field: selected items render as removable badges; picking happens in a
 * searchable {@link CpStack}.
 */
export function CpMultiRelationField({
    id,
    label,
    value,
    options,
    onChange,
    placeholder = 'Не выбрано',
    instructions,
    error,
}: {
    id?: string;
    label: string;
    value: number[];
    options: Option[];
    onChange: (value: number[]) => void;
    placeholder?: string;
    instructions?: string;
    error?: string;
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');

    const selected = options.filter((option) => value.includes(option.id));
    const q = query.trim().toLowerCase();
    const filtered =
        q === ''
            ? options
            : options.filter((option) => option.name.toLowerCase().includes(q));

    const toggle = (optionId: number) => {
        if (value.includes(optionId)) {
            onChange(value.filter((id) => id !== optionId));
        } else {
            onChange([...value, optionId]);
        }
    };

    const remove = (optionId: number) => {
        onChange(value.filter((id) => id !== optionId));
    };

    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            {instructions && (
                <p className="text-xs text-muted-foreground">{instructions}</p>
            )}

            <div className="flex flex-wrap items-center gap-2">
                {selected.length > 0 ? (
                    selected.map((option) => (
                        <Badge
                            key={option.id}
                            variant="secondary"
                            className="gap-1 pr-1"
                        >
                            {option.name}
                            <button
                                type="button"
                                aria-label={`Убрать ${option.name}`}
                                onClick={() => remove(option.id)}
                                className="rounded-sm p-0.5 hover:bg-muted"
                            >
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))
                ) : (
                    <span className="text-sm text-muted-foreground">
                        {placeholder}
                    </span>
                )}
            </div>

            <button
                type="button"
                id={id}
                onClick={() => {
                    setQuery('');
                    setOpen(true);
                }}
                className="flex w-full items-center justify-between gap-2 rounded-md border border-input bg-card px-3 py-2 text-sm transition-colors hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <span className="text-muted-foreground">Выбрать…</span>
                <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
            </button>

            <InputError message={error} />

            <CpStack open={open} onOpenChange={setOpen} title={label}>
                <div className="space-y-3">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            autoFocus
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Поиск…"
                            className="pl-8"
                        />
                    </div>

                    <ul className="space-y-0.5">
                        {filtered.map((option) => {
                            const isSelected = value.includes(option.id);

                            return (
                                <li key={option.id}>
                                    <button
                                        type="button"
                                        onClick={() => toggle(option.id)}
                                        className={cn(
                                            'flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-muted',
                                            isSelected &&
                                                'bg-primary/10 text-primary',
                                        )}
                                    >
                                        {option.name}
                                        {isSelected && (
                                            <Check className="size-4" />
                                        )}
                                    </button>
                                </li>
                            );
                        })}
                        {filtered.length === 0 && (
                            <li className="px-3 py-6 text-center text-sm text-muted-foreground">
                                Ничего не найдено
                            </li>
                        )}
                    </ul>
                </div>
            </CpStack>
        </div>
    );
}

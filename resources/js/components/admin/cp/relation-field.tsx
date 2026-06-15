import { Check, ChevronRight, Search, X } from 'lucide-react';
import { useState } from 'react';
import { CpStack } from '@/components/admin/cp/stack';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type Option = { id: number; name: string };

/**
 * Relationship fieldtype (Statamic-style): the selected item is shown as a control button that
 * opens a {@link CpStack} with a searchable list to pick from. Clearable to "not selected". Keeps
 * the form value as the related id (or null), so the surrounding `useForm` shape is unchanged.
 */
export function CpRelationField({
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
    value: number | null;
    options: Option[];
    onChange: (value: number | null) => void;
    placeholder?: string;
    instructions?: string;
    error?: string;
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');

    const selected = options.find((option) => option.id === value) ?? null;
    const q = query.trim().toLowerCase();
    const filtered = q === '' ? options : options.filter((option) => option.name.toLowerCase().includes(q));

    const choose = (next: number | null) => {
        onChange(next);
        setOpen(false);
    };

    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            {instructions && <p className="text-xs text-muted-foreground">{instructions}</p>}

            <div className="flex items-center gap-2">
                <button
                    type="button"
                    id={id}
                    onClick={() => {
                        setQuery('');
                        setOpen(true);
                    }}
                    className="flex flex-1 items-center justify-between gap-2 rounded-md border border-input bg-card px-3 py-2 text-sm transition-colors hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <span className={cn(selected ? 'text-foreground' : 'text-muted-foreground')}>
                        {selected ? selected.name : placeholder}
                    </span>
                    <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                </button>
                {selected && (
                    <button
                        type="button"
                        aria-label="Очистить"
                        onClick={() => onChange(null)}
                        className="rounded-md border border-input p-2 text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <X className="size-4" />
                    </button>
                )}
            </div>

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
                        <li>
                            <button
                                type="button"
                                onClick={() => choose(null)}
                                className={cn(
                                    'flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-muted',
                                    value === null && 'bg-primary/10 text-primary',
                                )}
                            >
                                — Не выбрано —
                                {value === null && <Check className="size-4" />}
                            </button>
                        </li>
                        {filtered.map((option) => (
                            <li key={option.id}>
                                <button
                                    type="button"
                                    onClick={() => choose(option.id)}
                                    className={cn(
                                        'flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-muted',
                                        value === option.id && 'bg-primary/10 text-primary',
                                    )}
                                >
                                    {option.name}
                                    {value === option.id && <Check className="size-4" />}
                                </button>
                            </li>
                        ))}
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

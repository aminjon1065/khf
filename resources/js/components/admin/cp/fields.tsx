import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { RichTextEditor } from '@/components/rich-text-editor';
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

type Option = { value: string; label: string };

/**
 * Statamic-style field shell: a label, optional instructions line under it, the control, then the
 * validation message — the consistent wrapper every CMS field uses. The typed fieldtypes below
 * compose it; use `CpField` directly for bespoke controls.
 */
export function CpField({
    id,
    label,
    instructions,
    error,
    children,
}: {
    id?: string;
    label?: string;
    instructions?: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            {label && <Label htmlFor={id}>{label}</Label>}
            {instructions && <p className="text-xs text-muted-foreground">{instructions}</p>}
            {children}
            <InputError message={error} />
        </div>
    );
}

export function CpTextField({
    id,
    label,
    instructions,
    error,
    value,
    onChange,
    type = 'text',
    placeholder,
}: {
    id?: string;
    label?: string;
    instructions?: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
    type?: string;
    placeholder?: string;
}) {
    return (
        <CpField id={id} label={label} instructions={instructions} error={error}>
            <Input
                id={id}
                type={type}
                value={value}
                placeholder={placeholder}
                onChange={(event) => onChange(event.target.value)}
            />
        </CpField>
    );
}

export function CpTextareaField({
    id,
    label,
    instructions,
    error,
    value,
    onChange,
    rows = 4,
}: {
    id?: string;
    label?: string;
    instructions?: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
    rows?: number;
}) {
    return (
        <CpField id={id} label={label} instructions={instructions} error={error}>
            <Textarea
                id={id}
                rows={rows}
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
        </CpField>
    );
}

export function CpSelectField({
    id,
    label,
    instructions,
    error,
    value,
    onChange,
    options,
    placeholder,
}: {
    id?: string;
    label?: string;
    instructions?: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
    options: Option[];
    placeholder?: string;
}) {
    return (
        <CpField id={id} label={label} instructions={instructions} error={error}>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger id={id}>
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </CpField>
    );
}

/**
 * Rich-text ("bard") fieldtype — the TipTap editor wrapped in the field shell. `editorKey` forces
 * a remount when switching locale (so the editor reloads the active locale's HTML).
 */
export function CpRichTextField({
    id,
    label,
    instructions,
    error,
    value,
    onChange,
    editorKey,
}: {
    id?: string;
    label?: string;
    instructions?: string;
    error?: string;
    value: string;
    onChange: (html: string) => void;
    editorKey?: string | number;
}) {
    return (
        <CpField id={id} label={label} instructions={instructions} error={error}>
            <RichTextEditor key={editorKey} value={value} onChange={onChange} />
        </CpField>
    );
}

/**
 * A boolean toggle rendered as a checkbox with an inline label + optional instructions, matching
 * the rest of the field set's spacing.
 */
export function CpToggleField({
    id,
    label,
    instructions,
    checked,
    onChange,
}: {
    id?: string;
    label: string;
    instructions?: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <div className="flex items-start gap-3">
            <Checkbox
                id={id}
                checked={checked}
                onCheckedChange={(value) => onChange(value === true)}
                className="mt-0.5"
            />
            <div className="space-y-0.5">
                <Label htmlFor={id} className="font-normal">
                    {label}
                </Label>
                {instructions && <p className="text-xs text-muted-foreground">{instructions}</p>}
            </div>
        </div>
    );
}

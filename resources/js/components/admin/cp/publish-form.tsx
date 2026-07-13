import { Link } from '@inertiajs/react';
import { Check, History } from 'lucide-react';
import { useRef, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { CpAutosaveIndicator } from '@/components/admin/cp/autosave-indicator';
import { Button } from '@/components/ui/button';
import type { AutosaveState } from '@/hooks/use-autosave';
import { useCmsFormShortcuts } from '@/hooks/use-cms-form-shortcuts';
import { cn } from '@/lib/utils';
import { RevisionsSlideOver } from './revisions-slide-over';

/**
 * Statamic-style two-column publish form: a main field column on the left and a sticky meta sidebar
 * on the right, under a header carrying the title and the Save / Cancel actions. Composed with
 * {@link CpPanel} (field groups) and {@link CpLocaleTabs} (per-locale switching) — see the module
 * forms for usage. Built on the КЧС brand tokens (D-19).
 */
export function CpPublishForm({
    title,
    backHref,
    onSubmit,
    processing = false,
    saveLabel = 'Сохранить',
    sidebar,
    children,
    modelInfo,
    headerActions,
    onPreview,
    autosave,
}: {
    title: string;
    backHref: string;
    onSubmit: (event: FormEvent) => void;
    processing?: boolean;
    saveLabel?: string;
    sidebar?: ReactNode;
    children: ReactNode;
    modelInfo?: { type: string; id: number | null };
    headerActions?: ReactNode;
    onPreview?: () => void;
    autosave?: { state: AutosaveState; savedAt: Date | null };
}) {
    const [historyOpen, setHistoryOpen] = useState(false);
    const formRef = useRef<HTMLFormElement>(null);

    useCmsFormShortcuts({
        onSave: () => formRef.current?.requestSubmit(),
        onPreview,
    });

    return (
        <form ref={formRef} onSubmit={onSubmit} className="p-4 sm:p-6">
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="text-xs text-muted-foreground">
                        ⌘S / Ctrl+S — сохранить
                        {onPreview ? ' · ⌘P / Ctrl+P — предпросмотр' : ''}
                    </p>
                    {autosave ? (
                        <CpAutosaveIndicator
                            state={autosave.state}
                            savedAt={autosave.savedAt}
                        />
                    ) : null}
                </div>
                <div className="flex items-center gap-2">
                    {headerActions}
                    {modelInfo && (
                        <>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                title="История версий"
                                disabled={!modelInfo.id}
                                onClick={() => setHistoryOpen(true)}
                            >
                                <History className="size-4" />
                            </Button>
                            <RevisionsSlideOver
                                open={historyOpen}
                                onOpenChange={setHistoryOpen}
                                modelType={modelInfo.type}
                                modelId={modelInfo.id}
                            />
                        </>
                    )}
                    <Button type="button" variant="outline" asChild>
                        <Link href={backHref}>Отмена</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {saveLabel}
                    </Button>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div className="min-w-0 space-y-6">{children}</div>
                {sidebar && (
                    <aside className="space-y-4 lg:sticky lg:top-20 lg:self-start">
                        {sidebar}
                    </aside>
                )}
            </div>
        </form>
    );
}

/**
 * A white field-group panel (Statamic "section"). Optional header with a title + description.
 */
export function CpPanel({
    title,
    description,
    children,
    className,
}: {
    title?: string;
    description?: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section
            className={cn(
                'rounded-lg border border-border bg-card shadow-sm',
                className,
            )}
        >
            {title && (
                <header className="border-b border-border px-4 py-3">
                    <h2 className="text-sm font-semibold">{title}</h2>
                    {description && (
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {description}
                        </p>
                    )}
                </header>
            )}
            <div className="space-y-4 p-4">{children}</div>
        </section>
    );
}

/**
 * Per-locale tab strip (Statamic localisation switcher). A check marks locales that already have
 * their primary field filled, so editors see translation gaps at a glance.
 */
export function CpLocaleTabs({
    locales,
    active,
    onChange,
    isComplete,
}: {
    locales: { code: string; native_name: string }[];
    active: string;
    onChange: (code: string) => void;
    isComplete: (code: string) => boolean;
}) {
    return (
        <div className="flex flex-wrap items-center gap-1 border-b border-border">
            {locales.map((locale) => {
                const on = locale.code === active;

                return (
                    <button
                        key={locale.code}
                        type="button"
                        onClick={() => onChange(locale.code)}
                        className={cn(
                            '-mb-px flex items-center gap-1.5 rounded-t-sm border-b-2 px-3 py-2 text-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                            on
                                ? 'border-primary font-medium text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {locale.native_name}
                        {isComplete(locale.code) && (
                            <Check className="size-3.5 text-hazard-normal" />
                        )}
                    </button>
                );
            })}
        </div>
    );
}

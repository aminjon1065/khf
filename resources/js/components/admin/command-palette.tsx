import { router } from '@inertiajs/react';
import { FileText, Plus, Search } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import type { ComponentType } from 'react';
import { navGroups } from '@/components/admin/nav';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { usePermissions } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import { create as createAlert } from '@/routes/admin/alerts';
import { create as createCategory } from '@/routes/admin/categories';
import { create as createDocument } from '@/routes/admin/documents';
import { create as createGuide } from '@/routes/admin/guides';
import { create as createIncident } from '@/routes/admin/incidents';
import { create as createPage } from '@/routes/admin/pages';
import { create as createPost } from '@/routes/admin/posts';
import { create as createTag } from '@/routes/admin/tags';

type Href = string | { url: string };
type ContentResult = {
    id: string;
    title: string;
    type: string;
    type_label: string;
    url: string;
};
type Command = {
    id: string;
    label: string;
    group: string;
    href: Href;
    icon: ComponentType<{ className?: string }>;
    permission?: string;
};

const createCommands: Omit<Command, 'icon' | 'group'>[] = [
    {
        id: 'new:post',
        label: 'Создать новость',
        href: createPost(),
        permission: 'posts.manage',
    },
    {
        id: 'new:page',
        label: 'Создать страницу',
        href: createPage(),
        permission: 'pages.manage',
    },
    {
        id: 'new:category',
        label: 'Создать рубрику',
        href: createCategory(),
        permission: 'categories.manage',
    },
    {
        id: 'new:tag',
        label: 'Создать тег',
        href: createTag(),
        permission: 'tags.manage',
    },
    {
        id: 'new:incident',
        label: 'Создать событие ЧС',
        href: createIncident(),
        permission: 'incidents.manage',
    },
    {
        id: 'new:alert',
        label: 'Создать оповещение',
        href: createAlert(),
        permission: 'alerts.manage',
    },
    {
        id: 'new:document',
        label: 'Создать документ',
        href: createDocument(),
        permission: 'documents.manage',
    },
    {
        id: 'new:guide',
        label: 'Создать памятку',
        href: createGuide(),
        permission: 'guides.manage',
    },
];

const toUrl = (href: Href): string =>
    typeof href === 'string' ? href : href.url;

type PaletteItem =
    | { kind: 'content'; result: ContentResult }
    | { kind: 'command'; command: Command };

/**
 * Statamic-style command palette (⌘K / Ctrl+K): fuzzy-jump to any CMS section or "create" action.
 * Permission-gated from the shared nav data; fully keyboard-driven (↑/↓/Enter/Esc). The body lives
 * in a child that only mounts while the dialog is open, so its query/selection reset on each open.
 */
export function CommandPalette() {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            if (
                (event.metaKey || event.ctrlKey) &&
                event.key.toLowerCase() === 'k'
            ) {
                event.preventDefault();
                setOpen((value) => !value);
            }
        };

        window.addEventListener('keydown', handler);

        return () => window.removeEventListener('keydown', handler);
    }, []);

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="inline-flex items-center gap-2 rounded-md border border-border bg-muted/40 px-2.5 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <Search className="size-4" />
                <span className="hidden sm:inline">Поиск…</span>
                <kbd className="ml-1 hidden rounded border border-border bg-card px-1.5 font-mono text-[10px] sm:inline">
                    ⌘K
                </kbd>
            </button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="top-[12%] max-w-xl translate-y-0 gap-0 overflow-hidden p-0">
                    <DialogTitle className="sr-only">
                        Командная панель
                    </DialogTitle>
                    <CommandPaletteBody onClose={() => setOpen(false)} />
                </DialogContent>
            </Dialog>
        </>
    );
}

function CommandPaletteBody({ onClose }: { onClose: () => void }) {
    const { can } = usePermissions();
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);
    const [contentResults, setContentResults] = useState<ContentResult[]>([]);
    const [contentLoading, setContentLoading] = useState(false);

    const commands = useMemo<Command[]>(() => {
        const nav: Command[] = navGroups.flatMap((group) =>
            group.items
                .filter((item) => !item.permission || can(item.permission))
                .map((item) => ({
                    id: `nav:${item.title}`,
                    label: item.title,
                    group: group.label,
                    href: item.href as Href,
                    icon: item.icon ?? Search,
                    permission: item.permission,
                })),
        );

        const create: Command[] = createCommands
            .filter((command) => !command.permission || can(command.permission))
            .map((command) => ({ ...command, group: 'Создать', icon: Plus }));

        return [...nav, ...create];
    }, [can]);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();

        if (q === '') {
            return commands;
        }

        return commands.filter(
            (command) =>
                command.label.toLowerCase().includes(q) ||
                command.group.toLowerCase().includes(q),
        );
    }, [commands, query]);

    useEffect(() => {
        const q = query.trim();

        if (q.length < 2) {
            setContentResults([]);

            return;
        }

        const timer = window.setTimeout(() => {
            setContentLoading(true);

            fetch(`/admin/api/search?q=${encodeURIComponent(q)}`)
                .then((response) => response.json())
                .then((data: { results?: ContentResult[] }) =>
                    setContentResults(data.results ?? []),
                )
                .catch(() => setContentResults([]))
                .finally(() => setContentLoading(false));
        }, 250);

        return () => window.clearTimeout(timer);
    }, [query]);

    const items = useMemo<PaletteItem[]>(() => {
        const content = contentResults.map(
            (result): PaletteItem => ({ kind: 'content', result }),
        );
        const nav = filtered.map(
            (command): PaletteItem => ({ kind: 'command', command }),
        );

        return [...content, ...nav];
    }, [contentResults, filtered]);

    const run = (item: PaletteItem | undefined) => {
        if (!item) {
            return;
        }

        onClose();

        if (item.kind === 'content') {
            router.visit(item.result.url);

            return;
        }

        router.visit(toUrl(item.command.href));
    };

    const onKeyDown = (event: React.KeyboardEvent) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive((value) =>
                items.length ? (value + 1) % items.length : 0,
            );
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive((value) =>
                items.length ? (value - 1 + items.length) % items.length : 0,
            );
        } else if (event.key === 'Enter') {
            event.preventDefault();
            run(items[active]);
        }
    };

    const current = Math.min(active, Math.max(items.length - 1, 0));

    return (
        <>
            <div className="flex items-center gap-2 border-b border-border px-4">
                <Search className="size-4 shrink-0 text-muted-foreground" />
                <input
                    autoFocus
                    value={query}
                    onChange={(event) => {
                        setQuery(event.target.value);
                        setActive(0);
                    }}
                    onKeyDown={onKeyDown}
                    placeholder="Поиск по материалам и разделам…"
                    className="h-12 w-full bg-transparent pr-8 text-sm outline-none placeholder:text-muted-foreground"
                />
                {contentLoading ? (
                    <span className="text-xs text-muted-foreground">…</span>
                ) : null}
            </div>

            <ul className="max-h-80 overflow-y-auto p-2">
                {items.length === 0 ? (
                    <li className="px-3 py-6 text-center text-sm text-muted-foreground">
                        {query.trim().length >= 2
                            ? 'Ничего не найдено'
                            : 'Введите минимум 2 символа для поиска материалов'}
                    </li>
                ) : (
                    items.map((item, index) => {
                        if (item.kind === 'content') {
                            return (
                                <li key={item.result.id}>
                                    <button
                                        type="button"
                                        onClick={() => run(item)}
                                        onMouseEnter={() => setActive(index)}
                                        className={cn(
                                            'flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-sm transition-colors',
                                            index === current
                                                ? 'bg-primary/10 text-primary'
                                                : 'text-foreground hover:bg-muted',
                                        )}
                                    >
                                        <FileText
                                            className={cn(
                                                'size-4 shrink-0',
                                                index === current
                                                    ? 'text-primary'
                                                    : 'text-muted-foreground',
                                            )}
                                        />
                                        <span className="flex-1">
                                            {item.result.title}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {item.result.type_label}
                                        </span>
                                    </button>
                                </li>
                            );
                        }

                        const command = item.command;
                        const Icon = command.icon;

                        return (
                            <li key={command.id}>
                                <button
                                    type="button"
                                    onClick={() => run(item)}
                                    onMouseEnter={() => setActive(index)}
                                    className={cn(
                                        'flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-sm transition-colors',
                                        index === current
                                            ? 'bg-primary/10 text-primary'
                                            : 'text-foreground hover:bg-muted',
                                    )}
                                >
                                    <Icon
                                        className={cn(
                                            'size-4 shrink-0',
                                            index === current
                                                ? 'text-primary'
                                                : 'text-muted-foreground',
                                        )}
                                    />
                                    <span className="flex-1">
                                        {command.label}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {command.group}
                                    </span>
                                </button>
                            </li>
                        );
                    })
                )}
            </ul>
        </>
    );
}

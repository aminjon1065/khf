type MenuPreviewNode = {
    id: number;
    translations: Record<string, { title?: string }>;
    preview_url?: string | null;
    children?: MenuPreviewNode[];
};

function nodeTitle(node: MenuPreviewNode, locale: string): string {
    return node.translations[locale]?.title?.trim() || '—';
}

function MegaColumn({
    node,
    locale,
}: {
    node: MenuPreviewNode;
    locale: string;
}) {
    const children = node.children ?? [];

    return (
        <div className="min-w-0 flex-1 space-y-2">
            <div className="text-sm font-semibold text-foreground">
                {nodeTitle(node, locale)}
            </div>
            {node.preview_url && (
                <div className="truncate text-xs text-muted-foreground">
                    {node.preview_url}
                </div>
            )}
            {children.length > 0 && (
                <ul className="space-y-1 border-l pl-3 text-sm text-muted-foreground">
                    {children.map((child) => (
                        <li key={child.id}>
                            <span className="text-foreground">
                                {nodeTitle(child, locale)}
                            </span>
                            {child.preview_url && (
                                <div className="truncate text-[11px]">
                                    {child.preview_url}
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export function MenuMegaPreview({
    items,
    defaultLocale,
    location,
}: {
    items: MenuPreviewNode[];
    defaultLocale: string;
    location: string;
}) {
    if (items.length === 0) {
        return (
            <div className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                Добавьте пункты меню, чтобы увидеть превью
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <p className="text-xs text-muted-foreground">
                Превью навигации (
                {location === 'primary' ? 'шапка сайта' : location}) на языке{' '}
                <span className="font-medium uppercase">{defaultLocale}</span>
            </p>
            <div className="overflow-hidden rounded-xl border bg-slate-950 text-slate-100 shadow-sm">
                <div className="border-b border-slate-800 px-4 py-2 text-xs tracking-wide text-slate-400 uppercase">
                    Desktop navigation
                </div>
                <div className="flex flex-wrap gap-1 border-b border-slate-800 px-4 py-2">
                    {items.map((item) => (
                        <span
                            key={item.id}
                            className="rounded-md px-3 py-1.5 text-sm font-medium text-slate-200"
                        >
                            {nodeTitle(item, defaultLocale)}
                            {(item.children?.length ?? 0) > 0 && (
                                <span className="ml-1 text-slate-500">▾</span>
                            )}
                        </span>
                    ))}
                </div>
                {location === 'primary' &&
                    items.some((item) => (item.children?.length ?? 0) > 0) && (
                        <div className="grid gap-4 bg-card p-4 text-card-foreground md:grid-cols-2 lg:grid-cols-3">
                            {items
                                .filter(
                                    (item) => (item.children?.length ?? 0) > 0,
                                )
                                .map((item) => (
                                    <MegaColumn
                                        key={item.id}
                                        node={item}
                                        locale={defaultLocale}
                                    />
                                ))}
                        </div>
                    )}
            </div>
        </div>
    );
}

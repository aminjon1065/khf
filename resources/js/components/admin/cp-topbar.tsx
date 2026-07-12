import { Link } from '@inertiajs/react';
import { ExternalLink, Menu } from 'lucide-react';
import { CommandPalette } from '@/components/admin/command-palette';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { ThemeToggle } from '@/components/theme-toggle';
import { home } from '@/routes';
import type { BreadcrumbItem } from '@/types';

/**
 * Slim global header of the control panel (Statamic-style): breadcrumbs on the left, a mobile nav
 * toggle, and a "view site" link on the right. Sticky, sits above the scrolling content.
 */
export function CpTopbar({
    breadcrumbs = [],
    onMenu,
}: {
    breadcrumbs?: BreadcrumbItem[];
    onMenu?: () => void;
}) {
    return (
        <header className="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-3 border-b border-border bg-card px-4 sm:px-6">
            <button
                type="button"
                onClick={onMenu}
                aria-label="Открыть меню"
                className="-ml-1 rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none md:hidden"
            >
                <Menu className="size-5" />
            </button>

            <div className="min-w-0 flex-1">
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <CommandPalette />

            <ThemeToggle className="size-9" iconClassName="size-4" />

            <Link
                href={home()}
                className="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <ExternalLink className="size-4" />
                <span className="hidden sm:inline">На сайт</span>
            </Link>
        </header>
    );
}

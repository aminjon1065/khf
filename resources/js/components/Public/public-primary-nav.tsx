import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';
import type { NavEntry, NavLeaf } from '@/types/public-layout';

const navTrigger =
    'inline-flex cursor-pointer items-center gap-1 rounded-md px-3.5 py-2 text-sm font-medium text-foreground/70 transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';
const navActive = 'bg-primary/10 text-primary';

type PublicPrimaryNavProps = {
    navEntries: NavEntry[];
    isActive: (href: string) => boolean;
    groupActive: (items: NavLeaf[]) => boolean;
};

export function PublicPrimaryNav({
    navEntries,
    isActive,
    groupActive,
}: PublicPrimaryNavProps) {
    const { t } = useTranslations();

    return (
        <div className="hidden border-t border-border bg-card lg:block">
            <nav
                aria-label={t('a11y.primary_nav')}
                className="mx-auto flex max-w-6xl items-center gap-0.5 px-4 py-2"
            >
                {navEntries.map((entry) =>
                    'items' in entry ? (
                        <DropdownMenu key={entry.label}>
                            <DropdownMenuTrigger
                                className={cn(
                                    navTrigger,
                                    groupActive(entry.items) && navActive,
                                )}
                            >
                                {entry.label}
                                <ChevronDown
                                    className="size-4 opacity-60"
                                    aria-hidden="true"
                                />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="start"
                                className="min-w-56"
                            >
                                {entry.items.map((sub) => (
                                    <DropdownMenuItem key={sub.href} asChild>
                                        <Link
                                            href={sub.href}
                                            className={cn(
                                                'w-full cursor-pointer',
                                                isActive(sub.href) &&
                                                    'text-primary',
                                            )}
                                        >
                                            {sub.label}
                                        </Link>
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    ) : (
                        <Link
                            key={entry.href}
                            href={entry.href}
                            className={cn(
                                navTrigger,
                                isActive(entry.href) && navActive,
                            )}
                        >
                            {entry.label}
                        </Link>
                    ),
                )}
            </nav>
        </div>
    );
}

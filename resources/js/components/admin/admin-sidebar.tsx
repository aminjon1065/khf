import { Link } from '@inertiajs/react';
import { CpUserMenu } from '@/components/admin/cp-user-menu';
import { navGroups } from '@/components/admin/nav';
import { AppEmblem } from '@/components/app-emblem';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { usePermissions } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import { dashboard as adminDashboard } from '@/routes/admin';

/**
 * Control-panel sidebar — a faithful Statamic-style nav (light panel, uppercase group labels,
 * accent-tinted active item, account menu at the foot) rendered on the КЧС brand tokens.
 * `onNavigate` lets the mobile drawer close itself on navigation.
 */
export function AdminSidebar({ onNavigate }: { onNavigate?: () => void }) {
    const { isCurrentUrl } = useCurrentUrl();
    const { can } = usePermissions();

    return (
        <div className="flex h-full w-64 flex-col border-r border-border bg-card">
            <Link
                href={adminDashboard()}
                prefetch
                onClick={onNavigate}
                className="flex items-center gap-2.5 border-b border-border px-4 py-3.5"
            >
                <AppEmblem className="size-8 shrink-0" />
                <span className="flex flex-col leading-tight">
                    <span className="text-sm font-semibold">КЧС · CMS</span>
                    <span className="text-xs text-muted-foreground">
                        Панель управления
                    </span>
                </span>
            </Link>

            <nav className="flex-1 overflow-y-auto px-3 py-4">
                {navGroups.map((group) => {
                    const items = group.items.filter(
                        (item) => !item.permission || can(item.permission),
                    );

                    if (items.length === 0) {
                        return null;
                    }

                    return (
                        <div key={group.label} className="mb-5">
                            <p className="px-3 pb-1.5 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                {group.label}
                            </p>
                            <ul className="space-y-0.5">
                                {items.map((item) => {
                                    const active = isCurrentUrl(item.href);

                                    return (
                                        <li key={item.title}>
                                            <Link
                                                href={item.href}
                                                prefetch
                                                onClick={onNavigate}
                                                className={cn(
                                                    'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                                    active
                                                        ? 'bg-primary/10 font-medium text-primary'
                                                        : 'text-foreground/75 hover:bg-muted hover:text-foreground',
                                                )}
                                            >
                                                {item.icon && (
                                                    <item.icon
                                                        className={cn(
                                                            'size-4 shrink-0',
                                                            active
                                                                ? 'text-primary'
                                                                : 'text-muted-foreground',
                                                        )}
                                                    />
                                                )}
                                                <span>{item.title}</span>
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    );
                })}
            </nav>

            <div className="border-t border-border p-3">
                <CpUserMenu />
            </div>
        </div>
    );
}

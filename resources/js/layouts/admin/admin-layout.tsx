import { useState } from 'react';
import { AdminSidebar } from '@/components/admin/admin-sidebar';
import { CpTopbar } from '@/components/admin/cp-topbar';
import type { BreadcrumbItem } from '@/types';

/**
 * Control-panel shell — a Statamic-style layout: a fixed light sidebar, a slim global header, and
 * the scrolling content area on a soft neutral background. Mobile collapses the sidebar into a
 * drawer. Built on the КЧС brand tokens, so it adapts to light/dark like the rest of the app.
 */
export default function AdminLayout({
    children,
    breadcrumbs = [],
}: {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}) {
    const [mobileOpen, setMobileOpen] = useState(false);

    return (
        <div className="flex min-h-screen bg-muted/40">
            <aside className="hidden md:block">
                <div className="sticky top-0 h-screen">
                    <AdminSidebar />
                </div>
            </aside>

            {mobileOpen && (
                <div className="fixed inset-0 z-40 md:hidden">
                    <button
                        type="button"
                        aria-label="Закрыть меню"
                        className="absolute inset-0 bg-black/40"
                        onClick={() => setMobileOpen(false)}
                    />
                    <div className="absolute inset-y-0 left-0 h-full shadow-xl">
                        <AdminSidebar onNavigate={() => setMobileOpen(false)} />
                    </div>
                </div>
            )}

            <div className="flex min-w-0 flex-1 flex-col">
                <CpTopbar breadcrumbs={breadcrumbs} onMenu={() => setMobileOpen(true)} />
                <main className="flex-1">{children}</main>
            </div>
        </div>
    );
}

import { Link } from '@inertiajs/react';
import { ExternalLink, LayoutDashboard, Plus, Settings } from 'lucide-react';
import { AppEmblem } from '@/components/app-emblem';

type AdminBarProps = {
    pageId?: number;
    postId?: number;
};

export function AdminBar({ pageId, postId }: AdminBarProps) {
    return (
        <div className="z-55 flex w-full items-center justify-between border-b border-slate-700 bg-[#1e293b] px-4 py-1.5 text-xs font-medium text-slate-100 shadow-sm print:hidden">
            <div className="flex items-center gap-5">
                {/* Logo and CMS link */}
                <Link
                    href="/admin"
                    className="flex items-center gap-2 text-slate-200 transition-colors hover:text-white"
                >
                    <AppEmblem className="app-logo-emblem size-4 shrink-0" />
                    <span className="font-bold tracking-tight">КЧС · CMS</span>
                </Link>

                <div className="h-3 w-px bg-slate-700" />

                {/* Dashboard link */}
                <Link
                    href="/admin"
                    className="flex items-center gap-1.5 text-slate-300 transition-colors hover:text-white"
                >
                    <LayoutDashboard className="size-3.5 text-slate-400" />
                    <span>Панель управления</span>
                </Link>

                {/* Quick edit if on page/post */}
                {postId && (
                    <>
                        <div className="h-3 w-px bg-slate-700" />
                        <Link
                            href={`/admin/posts/${postId}/edit`}
                            className="flex items-center gap-1.5 font-semibold text-slate-300 transition-colors hover:text-white"
                        >
                            <Settings className="size-3.5 text-slate-400" />
                            <span>Редактировать новость</span>
                        </Link>
                    </>
                )}

                {pageId && (
                    <>
                        <div className="h-3 w-px bg-slate-700" />
                        <Link
                            href={`/admin/pages/${pageId}/edit`}
                            className="flex items-center gap-1.5 font-semibold text-slate-300 transition-colors hover:text-white"
                        >
                            <Settings className="size-3.5 text-slate-400" />
                            <span>Редактировать страницу</span>
                        </Link>
                    </>
                )}

                <div className="h-3 w-px bg-slate-700" />

                {/* Create options */}
                <div className="flex items-center gap-3">
                    <Link
                        href="/admin/posts/create"
                        className="flex items-center gap-1 text-slate-400 transition-colors hover:text-white"
                        title="Создать новость"
                    >
                        <Plus className="size-3 text-slate-500" />
                        <span>Новость</span>
                    </Link>
                    <Link
                        href="/admin/pages/create"
                        className="flex items-center gap-1 text-slate-400 transition-colors hover:text-white"
                        title="Создать страницу"
                    >
                        <Plus className="size-3 text-slate-500" />
                        <span>Страница</span>
                    </Link>
                </div>
            </div>

            <div className="flex items-center gap-4 text-[11px] text-slate-400">
                <Link
                    href="/admin"
                    className="flex items-center gap-1 transition-colors hover:text-white"
                >
                    <span>Админ-панель</span>
                    <ExternalLink className="size-3 text-slate-500" />
                </Link>
            </div>
        </div>
    );
}

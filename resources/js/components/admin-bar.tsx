import { Link } from '@inertiajs/react';
import { ExternalLink, LayoutDashboard, Plus, Settings } from 'lucide-react';
import { AppEmblem } from '@/components/app-emblem';

type AdminBarProps = {
    pageId?: number;
    postId?: number;
};

export function AdminBar({ pageId, postId }: AdminBarProps) {
    return (
        <div className="w-full bg-[#1e293b] text-slate-100 py-1.5 px-4 text-xs font-medium border-b border-slate-700 shadow-sm print:hidden flex items-center justify-between z-55">
            <div className="flex items-center gap-5">
                {/* Logo and CMS link */}
                <Link
                    href="/admin"
                    className="flex items-center gap-2 hover:text-white transition-colors text-slate-200"
                >
                    <AppEmblem className="size-4 shrink-0 app-logo-emblem" />
                    <span className="font-bold tracking-tight">КЧС · CMS</span>
                </Link>

                <div className="h-3 w-px bg-slate-700" />

                {/* Dashboard link */}
                <Link
                    href="/admin"
                    className="flex items-center gap-1.5 hover:text-white transition-colors text-slate-300"
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
                            className="flex items-center gap-1.5 hover:text-white transition-colors text-slate-300 font-semibold"
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
                            className="flex items-center gap-1.5 hover:text-white transition-colors text-slate-300 font-semibold"
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
                        className="flex items-center gap-1 hover:text-white transition-colors text-slate-400"
                        title="Создать новость"
                    >
                        <Plus className="size-3 text-slate-500" />
                        <span>Новость</span>
                    </Link>
                    <Link
                        href="/admin/pages/create"
                        className="flex items-center gap-1 hover:text-white transition-colors text-slate-400"
                        title="Создать страницу"
                    >
                        <Plus className="size-3 text-slate-500" />
                        <span>Страница</span>
                    </Link>
                </div>
            </div>

            <div className="flex items-center gap-4 text-slate-400 text-[11px]">
                <Link
                    href="/admin"
                    className="hover:text-white transition-colors flex items-center gap-1"
                >
                    <span>Админ-панель</span>
                    <ExternalLink className="size-3 text-slate-500" />
                </Link>
            </div>
        </div>
    );
}

import { usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';

/**
 * Control-panel account menu pinned to the foot of the CP sidebar (Statamic-style). Standalone —
 * unlike `NavUser` it does not depend on the shadcn Sidebar context, so it works in the custom shell.
 */
export function CpUserMenu() {
    const { auth } = usePage().props;

    if (!auth?.user) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className="flex w-full items-center gap-2 rounded-md border border-border bg-card px-2 py-1.5 text-left transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    data-test="cp-user-menu"
                >
                    <UserInfo user={auth.user} />
                    <ChevronsUpDown className="ml-auto size-4 text-muted-foreground" />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-56 rounded-lg" align="end" side="top">
                <UserMenuContent user={auth.user} />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

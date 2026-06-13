import { Link } from '@inertiajs/react';
import { Home, Map, Newspaper, Menu as MenuIcon } from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useTranslations } from '@/hooks/use-translations';
import { index as newsIndex } from '@/routes/news';
import { cn } from '@/lib/utils';
import { usePage } from '@inertiajs/react';
import { Sheet, SheetTrigger, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import AppLogoIcon from '@/components/app-logo-icon';
import { AppHeaderMobileMenu } from '@/components/app-header-mobile-menu'; // We will extract the mobile menu

export function BottomNavigation() {
    const { isCurrentUrl } = useCurrentUrl();
    const { t } = useTranslations();
    const { locale } = usePage().props as { locale: string };

    const navItems = [
        {
            title: t('common.home', 'Главная'),
            href: '/',
            icon: Home,
        },
        {
            title: t('common.news', 'Новости'),
            href: newsIndex({ locale }).url,
            icon: Newspaper,
        },
        {
            title: t('common.map', 'Карта'),
            href: '/map', // Assuming map route is /map or we use a correct route
            icon: Map,
        },
    ];

    return (
        <div className="fixed bottom-0 left-0 right-0 z-50 flex h-16 items-center justify-around border-t bg-background px-2 pb-safe pt-1 sm:hidden">
            {navItems.map((item) => {
                const isActive = isCurrentUrl(item.href);
                return (
                    <Link
                        key={item.title}
                        href={item.href}
                        className={cn(
                            'flex flex-col items-center justify-center space-y-1 px-3 py-1 transition-colors',
                            isActive ? 'text-primary' : 'text-muted-foreground hover:text-foreground'
                        )}
                    >
                        <item.icon className={cn('h-5 w-5', isActive && 'fill-primary/20')} />
                        <span className="text-[10px] font-medium leading-none">{item.title}</span>
                    </Link>
                );
            })}

            {/* Mobile search or other quick actions can be added here */}
        </div>
    );
}

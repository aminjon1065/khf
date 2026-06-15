import { Link, usePage } from '@inertiajs/react';
import { Home, Map, Newspaper } from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';
import { welcome } from '@/routes';
import { index as mapIndex } from '@/routes/map';
import { index as newsIndex } from '@/routes/news';

export function BottomNavigation() {
    const { isCurrentUrl } = useCurrentUrl();
    const { t } = useTranslations();
    const { locale } = usePage().props as { locale: string };

    const navItems = [
        {
            title: t('nav.home'),
            href: welcome({ locale }).url,
            icon: Home,
        },
        {
            title: t('nav.news'),
            href: newsIndex({ locale }).url,
            icon: Newspaper,
        },
        {
            title: t('nav.map'),
            href: mapIndex({ locale }).url,
            icon: Map,
        },
    ];

    return (
        <nav
            aria-label={t('a11y.primary_nav')}
            className="fixed bottom-0 left-0 right-0 z-50 flex h-16 items-center justify-around border-t bg-background px-2 pb-safe pt-1 sm:hidden"
        >
            {navItems.map((item) => {
                const isActive = isCurrentUrl(item.href);

                return (
                    <Link
                        key={item.title}
                        href={item.href}
                        aria-current={isActive ? 'page' : undefined}
                        className={cn(
                            'flex flex-col items-center justify-center space-y-1 px-3 py-1 transition-colors',
                            isActive ? 'text-primary' : 'text-muted-foreground hover:text-foreground'
                        )}
                    >
                        <item.icon className={cn('h-5 w-5', isActive && 'fill-primary/20')} aria-hidden="true" />
                        <span className="text-[10px] font-medium leading-none">{item.title}</span>
                    </Link>
                );
            })}

            {/* Mobile search or other quick actions can be added here */}
        </nav>
    );
}

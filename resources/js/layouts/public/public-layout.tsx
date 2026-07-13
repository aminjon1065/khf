import { useState } from 'react';
import { AccessibilityToolbar } from '@/components/accessibility-toolbar';
import { AdminBar } from '@/components/admin-bar';
import { AlertBanner } from '@/components/alert-banner';
import { BottomNavigation } from '@/components/Public/bottom-navigation';
import { GlobalSearchModal } from '@/components/Public/GlobalSearchModal';
import { PreviewBanner } from '@/components/Public/preview-banner';
import { PublicFooter } from '@/components/Public/public-footer';
import { PublicHeader } from '@/components/Public/public-header';
import { PublicUtilityStrip } from '@/components/Public/public-utility-strip';
import { usePublicLayoutProps } from '@/hooks/use-public-layout-props';
import { usePublicNavigation } from '@/hooks/use-public-navigation';
import { useTranslations } from '@/hooks/use-translations';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { t } = useTranslations();
    const [isSearchOpen, setIsSearchOpen] = useState(false);
    const [isA11yOpen, setIsA11yOpen] = useState(false);
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    const layout = usePublicLayoutProps();
    const { navEntries, isActive, groupActive } = usePublicNavigation(
        layout.locale,
        layout.currentUrl,
        layout.rawPrimary,
    );

    return (
        <div className="flex min-h-screen flex-col overflow-x-clip bg-background font-sans text-foreground antialiased selection:bg-primary/20">
            <a
                href="#main-content"
                className="sr-only rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[100]"
            >
                {t('a11y.skip_to_content')}
            </a>
            {layout.canManage && (
                <AdminBar pageId={layout.pageId} postId={layout.postId} />
            )}
            <PreviewBanner />
            {isA11yOpen && (
                <AccessibilityToolbar onClose={() => setIsA11yOpen(false)} />
            )}

            <div className="h-[3px] bg-signal print:hidden" />

            <PublicUtilityStrip onSearchOpen={() => setIsSearchOpen(true)} />

            <AlertBanner />

            <PublicHeader
                locale={layout.locale}
                locales={layout.locales}
                localeSwitch={layout.localeSwitch}
                navEntries={navEntries}
                isActive={isActive}
                groupActive={groupActive}
                isRedState={layout.isRedState}
                headerClass={layout.headerClass}
                buttonClass={layout.buttonClass}
                isA11yOpen={isA11yOpen}
                isMobileMenuOpen={isMobileMenuOpen}
                onA11yToggle={() => setIsA11yOpen(!isA11yOpen)}
                onMobileMenuChange={setIsMobileMenuOpen}
                onSearchOpen={() => setIsSearchOpen(true)}
                onA11yOpen={() => setIsA11yOpen(true)}
            />

            <main
                id="main-content"
                tabIndex={-1}
                className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 pb-[calc(4.5rem+env(safe-area-inset-bottom,0px))] focus:outline-none sm:py-12 sm:pb-12 lg:py-16 lg:pb-16"
            >
                {children}
            </main>

            <PublicFooter
                locale={layout.locale}
                hotline={layout.hotline}
                socialLinks={layout.socialLinks}
                rawFooter={layout.rawFooter}
                footerContent={layout.footerContent}
                president={layout.president}
                onA11yOpen={() => setIsA11yOpen(true)}
            />

            <BottomNavigation />
            <GlobalSearchModal
                isOpen={isSearchOpen}
                setIsOpen={setIsSearchOpen}
            />
        </div>
    );
}

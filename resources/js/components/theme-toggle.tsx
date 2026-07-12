import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

const APPEARANCE_ORDER: Appearance[] = ['light', 'dark', 'system'];

const APPEARANCE_ICONS: Record<Appearance, LucideIcon> = {
    light: Sun,
    dark: Moon,
    system: Monitor,
};

export function nextAppearance(current: Appearance): Appearance {
    const index = APPEARANCE_ORDER.indexOf(current);
    const nextIndex = index === -1 ? 0 : (index + 1) % APPEARANCE_ORDER.length;

    return APPEARANCE_ORDER[nextIndex]!;
}

type ThemeToggleProps = {
    className?: string;
    iconClassName?: string;
    /** Optional visible label (e.g. mobile drawer). */
    label?: string;
};

/**
 * Compact theme control: cycles light → dark → system and persists via the shared
 * appearance hook (localStorage + cookie for SSR).
 */
export function ThemeToggle({
    className,
    iconClassName,
    label,
}: ThemeToggleProps) {
    const { appearance, updateAppearance } = useAppearance();
    const { t } = useTranslations();
    const Icon = APPEARANCE_ICONS[appearance];
    const modeLabel = t(`theme.${appearance}`);
    const accessibleLabel = `${t('theme.toggle')} (${modeLabel})`;

    return (
        <button
            type="button"
            onClick={() => updateAppearance(nextAppearance(appearance))}
            className={cn(
                'inline-flex cursor-pointer items-center justify-center rounded-md p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                className,
            )}
            aria-label={accessibleLabel}
            title={accessibleLabel}
            data-test="theme-toggle"
        >
            <Icon className={cn('size-5', iconClassName)} aria-hidden="true" />
            {label ? <span className="text-sm">{label}</span> : null}
        </button>
    );
}

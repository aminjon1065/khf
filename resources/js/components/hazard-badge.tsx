import { Flame, OctagonAlert, ShieldCheck, TriangleAlert } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export type HazardLevel = 'normal' | 'elevated' | 'danger' | 'critical';

/**
 * Hazard-level indicator (ТЗ Приложение Г). Accessibility-critical: meaning is carried by
 * colour + icon + text together, never colour alone — so colour-blind and screen-reader users get
 * the same signal (this is why the yellow `elevated` level uses dark text and a distinct icon).
 * Hazard colours are theme-independent for instant recognition. `label` is the locale-resolved
 * text supplied by the server.
 */
const LEVELS: Record<HazardLevel, { icon: LucideIcon; tone: string }> = {
    normal: {
        icon: ShieldCheck,
        tone: 'bg-hazard-normal text-hazard-normal-foreground',
    },
    elevated: {
        icon: TriangleAlert,
        tone: 'bg-hazard-elevated text-hazard-elevated-foreground',
    },
    danger: {
        icon: Flame,
        tone: 'bg-hazard-danger text-hazard-danger-foreground',
    },
    critical: {
        icon: OctagonAlert,
        tone: 'bg-hazard-critical text-hazard-critical-foreground',
    },
};

export function HazardBadge({
    level,
    label,
    size = 'default',
    className,
}: {
    level: HazardLevel;
    label: string;
    size?: 'sm' | 'default';
    className?: string;
}) {
    const { icon: Icon, tone } = LEVELS[level] ?? LEVELS.normal;

    return (
        <span
            role="status"
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full font-medium whitespace-nowrap',
                size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm',
                tone,
                className,
            )}
        >
            <Icon
                className={cn(
                    'shrink-0',
                    size === 'sm' ? 'size-3' : 'size-3.5',
                )}
                aria-hidden
            />
            {label}
        </span>
    );
}

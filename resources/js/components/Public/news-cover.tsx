import { useState } from 'react';
import { AppEmblem } from '@/components/app-emblem';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

type NewsCoverProps = {
    src: string | null | undefined;
    alt?: string;
    locale?: string;
    aspect?: string;
    className?: string;
    imgClassName?: string;
    loading?: 'eager' | 'lazy';
};

/**
 * News cover image with brand emblem placeholder and graceful error fallback.
 */
export function NewsCover({
    src,
    alt = '',
    locale,
    aspect = 'aspect-[16/10]',
    className,
    imgClassName,
    loading = 'lazy',
}: NewsCoverProps) {
    const { t } = useTranslations();
    const [hasError, setHasError] = useState(false);
    const showPlaceholder = !src || hasError;

    return (
        <div
            className={cn(
                'relative w-full overflow-hidden bg-muted',
                aspect,
                className,
            )}
        >
            {showPlaceholder ? (
                <div
                    className="absolute inset-0 flex items-center justify-center bg-secondary"
                    role="img"
                    aria-label={t('common.cover_placeholder_alt')}
                >
                    <AppEmblem
                        locale={locale}
                        className="size-12 text-muted-foreground/30 sm:size-14"
                        alt=""
                    />
                </div>
            ) : (
                <img
                    src={src}
                    alt={alt}
                    loading={loading}
                    onError={() => setHasError(true)}
                    className={cn('h-full w-full object-cover', imgClassName)}
                />
            )}
        </div>
    );
}

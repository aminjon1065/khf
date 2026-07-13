/**
 * Official State Flag of the Republic of Tajikistan (Resolution №499).
 * Raster asset — keep aspect ratio ~2:1 and do not recolour.
 */
export function TajikistanFlag({
    className = 'h-3.5 w-7',
    alt = '',
}: {
    className?: string;
    alt?: string;
}) {
    return (
        <img
            src="/images/Flag_of_Tajikistan.png"
            alt={alt}
            className={`shrink-0 rounded-xs border border-border object-cover shadow-xs ${className}`}
            width={28}
            height={14}
            decoding="async"
        />
    );
}

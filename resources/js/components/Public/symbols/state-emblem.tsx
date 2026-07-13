/**
 * State Emblem (Coat of Arms) of the Republic of Tajikistan.
 * Official raster asset — keep at readable size (≥20px) and do not recolour.
 */
export function TajikistanEmblem({
    className = 'size-9',
    alt = '',
}: {
    className?: string;
    alt?: string;
}) {
    return (
        <img
            src="/images/Emblem_of_Tajikistan.png"
            alt={alt}
            className={className}
            width={40}
            height={40}
            decoding="async"
        />
    );
}

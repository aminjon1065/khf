import { cn } from '@/lib/utils';

type Props = {
    imageUrl: string;
    focalX: number;
    focalY: number;
    onChange: (focalX: number, focalY: number) => void;
    className?: string;
};

export function mediaFocalPosition(focalX: number, focalY: number): string {
    return `${focalX}% ${focalY}%`;
}

/**
 * Click-to-set focal point editor for library images (object-position anchor).
 */
export function FocalPointPicker({
    imageUrl,
    focalX,
    focalY,
    onChange,
    className,
}: Props) {
    const setFromEvent = (event: React.MouseEvent<HTMLDivElement>) => {
        const rect = event.currentTarget.getBoundingClientRect();
        const x = Math.min(
            100,
            Math.max(0, ((event.clientX - rect.left) / rect.width) * 100),
        );
        const y = Math.min(
            100,
            Math.max(0, ((event.clientY - rect.top) / rect.height) * 100),
        );

        onChange(Math.round(x * 10) / 10, Math.round(y * 10) / 10);
    };

    return (
        <div className={cn('space-y-2', className)}>
            <p className="text-xs text-muted-foreground">
                Кликните по изображению, чтобы задать точку фокуса для обрезки и
                превью.
            </p>
            <div
                role="button"
                tabIndex={0}
                aria-label="Задать точку фокуса"
                onClick={setFromEvent}
                onKeyDown={(event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                    }
                }}
                className="relative cursor-crosshair overflow-hidden rounded-md border border-border bg-muted"
            >
                <img
                    src={imageUrl}
                    alt=""
                    className="max-h-56 w-full object-cover"
                    style={{
                        objectPosition: mediaFocalPosition(focalX, focalY),
                    }}
                    draggable={false}
                />
                <span
                    aria-hidden
                    className="pointer-events-none absolute size-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-primary shadow-md"
                    style={{ left: `${focalX}%`, top: `${focalY}%` }}
                />
            </div>
            <p className="text-xs text-muted-foreground">
                X: {focalX.toFixed(1)}% · Y: {focalY.toFixed(1)}%
            </p>
        </div>
    );
}

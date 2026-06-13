import { NodeViewWrapper } from '@tiptap/react';
import { useCallback, useRef, useState, useEffect } from 'react';
import { AlignLeft, AlignCenter, AlignRight } from 'lucide-react';
import { Toggle } from '@/components/ui/toggle';

export function ImageNodeView({ node, updateAttributes, selected }: any) {
    const { src, alt, title, width, height, float } = node.attrs;
    const imageRef = useRef<HTMLImageElement>(null);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const [isResizing, setIsResizing] = useState(false);
    
    // Fallbacks for display depending on alignment
    const wrapperStyle: React.CSSProperties = {
        position: 'relative',
        display: float === 'none' || !float ? 'block' : 'inline-block',
        float: float !== 'none' ? float : undefined,
        margin: float === 'left' ? '0 1em 1em 0' : float === 'right' ? '0 0 1em 1em' : float === 'center' ? '0 auto 1em auto' : '0 0 1em 0',
        textAlign: float === 'center' ? 'center' : undefined,
        clear: 'both',
        maxWidth: '100%',
    };

    const imageStyle: React.CSSProperties = {
        width: width ? `${width}px` : 'auto',
        height: height ? `${height}px` : 'auto',
        maxWidth: '100%',
        display: float === 'center' ? 'inline-block' : 'block',
        outline: selected ? '2px solid hsl(var(--primary))' : 'none',
        outlineOffset: '2px',
        cursor: 'default',
    };

    // Resizing logic
    const handleMouseDown = (e: React.MouseEvent, corner: string) => {
        e.preventDefault();
        setIsResizing(true);
        const startX = e.clientX;
        const startY = e.clientY;
        const startWidth = imageRef.current?.offsetWidth || 0;
        const startHeight = imageRef.current?.offsetHeight || 0;

        const onMouseMove = (moveEvent: MouseEvent) => {
            const dx = moveEvent.clientX - startX;
            const dy = moveEvent.clientY - startY;

            let newWidth = startWidth;
            let newHeight = startHeight;

            if (corner.includes('right')) newWidth += dx;
            if (corner.includes('left')) newWidth -= dx;
            if (corner.includes('bottom')) newHeight += dy;
            if (corner.includes('top')) newHeight -= dy;

            // Maintain aspect ratio approximately
            const ratio = startWidth / startHeight;
            newHeight = newWidth / ratio;

            if (newWidth > 50) {
                updateAttributes({ width: Math.round(newWidth), height: Math.round(newHeight) });
            }
        };

        const onMouseUp = () => {
            setIsResizing(false);
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
        };

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    };

    const setAlignment = (align: 'left' | 'center' | 'right' | 'none') => {
        if (align === 'center') {
            updateAttributes({ float: 'none', display: 'block', margin: '0 auto 1em auto' });
        } else if (align === 'left' || align === 'right') {
            updateAttributes({ float: align, display: 'inline-block', margin: align === 'left' ? '0 1em 1em 0' : '0 0 1em 1em' });
        } else {
            updateAttributes({ float: 'none', display: 'block', margin: '0 0 1em 0' });
        }
    };

    return (
        <NodeViewWrapper as="span" style={{ display: 'inline-block', width: float === 'none' ? '100%' : 'auto', float: float !== 'none' ? float as any : undefined }}>
            <div
                ref={wrapperRef}
                style={wrapperStyle}
                className="image-node-wrapper group"
                contentEditable={false}
            >
                <img
                    ref={imageRef}
                    src={src}
                    alt={alt}
                    title={title}
                    style={imageStyle}
                    className="rounded-md"
                />

                {selected && (
                    <>
                        {/* Alignment Toolbar */}
                        <div className="absolute top-2 left-1/2 -translate-x-1/2 flex items-center gap-1 bg-background/90 backdrop-blur-sm border shadow-md rounded-md p-1 z-50">
                            <Toggle
                                size="sm"
                                pressed={float === 'left'}
                                onPressedChange={() => setAlignment(float === 'left' ? 'none' : 'left')}
                                aria-label="Обтекание слева"
                            >
                                <AlignLeft className="size-4" />
                            </Toggle>
                            <Toggle
                                size="sm"
                                pressed={float === 'center' || (!float && wrapperStyle.margin?.toString().includes('auto'))}
                                onPressedChange={() => setAlignment('center')}
                                aria-label="По центру"
                            >
                                <AlignCenter className="size-4" />
                            </Toggle>
                            <Toggle
                                size="sm"
                                pressed={float === 'right'}
                                onPressedChange={() => setAlignment(float === 'right' ? 'none' : 'right')}
                                aria-label="Обтекание справа"
                            >
                                <AlignRight className="size-4" />
                            </Toggle>
                        </div>

                        {/* Resize Handles */}
                        <div
                            className="absolute -bottom-1.5 -right-1.5 w-3 h-3 bg-primary border-2 border-background cursor-se-resize rounded-full shadow-sm z-50"
                            onMouseDown={(e) => handleMouseDown(e, 'bottom-right')}
                        />
                        <div
                            className="absolute -bottom-1.5 -left-1.5 w-3 h-3 bg-primary border-2 border-background cursor-sw-resize rounded-full shadow-sm z-50"
                            onMouseDown={(e) => handleMouseDown(e, 'bottom-left')}
                        />
                        <div
                            className="absolute -top-1.5 -right-1.5 w-3 h-3 bg-primary border-2 border-background cursor-ne-resize rounded-full shadow-sm z-50"
                            onMouseDown={(e) => handleMouseDown(e, 'top-right')}
                        />
                        <div
                            className="absolute -top-1.5 -left-1.5 w-3 h-3 bg-primary border-2 border-background cursor-nw-resize rounded-full shadow-sm z-50"
                            onMouseDown={(e) => handleMouseDown(e, 'top-left')}
                        />
                    </>
                )}
            </div>
        </NodeViewWrapper>
    );
}

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { CpTextField, CpRichTextField } from '@/components/admin/cp/fields';
import { CpStack } from '@/components/admin/cp/stack';
import {
    MediaBrowserFilters,
    MediaBrowserGrid,
    type MediaLibraryItem,
    useMediaLibrary,
} from '@/components/admin/media-browser';
import { ImageIcon, Plus, Trash, GripVertical } from 'lucide-react';
import { useState } from 'react';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    type DragEndEvent,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import type { BlockTypeDefinition } from '@/types/cms';

export type BlockData = {
    id: string;
    type: string;
    data: Record<string, any>;
};

type CpBlocksFieldProps = {
    value: BlockData[];
    onChange: (blocks: BlockData[]) => void;
    editorKey?: string;
    blockTypes?: BlockTypeDefinition[];
};

const FALLBACK_BLOCK_TYPES: BlockTypeDefinition[] = [
    { type: 'text', label: 'Текст', defaults: { content: '' } },
    {
        type: 'image_gallery',
        label: 'Галерея изображений',
        defaults: { images: [{ url: '', alt: '', caption: '' }] },
    },
    { type: 'news_list', label: 'Лента новостей', defaults: { count: '6' } },
    {
        type: 'map_widget',
        label: 'Виджет карты',
        defaults: { lat: '38.5598', lng: '68.7870', zoom: '10', title: '' },
    },
    { type: 'cta', label: 'Призыв к действию', defaults: { label: '', url: '' } },
    {
        type: 'accordion',
        label: 'Аккордеон',
        defaults: { items: [{ title: '', content: '' }] },
    },
    {
        type: 'table',
        label: 'Таблица',
        defaults: {
            caption: '',
            headers: ['Колонка 1', 'Колонка 2'],
            rows: [['', '']],
        },
    },
    {
        type: 'contacts',
        label: 'Контакты',
        defaults: {
            heading: '',
            address: '',
            phone: '',
            email: '',
            hours: '',
        },
    },
];

export function CpBlocksField({
    value = [],
    onChange,
    editorKey,
    blockTypes = FALLBACK_BLOCK_TYPES,
}: CpBlocksFieldProps) {
    const blocks = Array.isArray(value) ? value : [];

    const addBlock = (type: string) => {
        const definition = blockTypes.find((item) => item.type === type);
        const newBlock: BlockData = {
            id: Math.random().toString(36).substring(2, 9),
            type,
            data: structuredClone(definition?.defaults ?? {}),
        };
        onChange([...blocks, newBlock]);
    };

    const removeBlock = (id: string) => {
        onChange(blocks.filter((b) => b.id !== id));
    };

    const updateBlockData = (id: string, key: string, val: any) => {
        onChange(
            blocks.map((b) => {
                if (b.id === id) {
                    return { ...b, data: { ...b.data, [key]: val } };
                }
                return b;
            })
        );
    };

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        const oldIndex = blocks.findIndex((block) => block.id === active.id);
        const newIndex = blocks.findIndex((block) => block.id === over.id);

        if (oldIndex === -1 || newIndex === -1) {
            return;
        }

        onChange(arrayMove(blocks, oldIndex, newIndex));
    };

    return (
        <div className="space-y-4">
            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={handleDragEnd}
            >
                <SortableContext
                    items={blocks.map((block) => block.id)}
                    strategy={verticalListSortingStrategy}
                >
                    {blocks.map((block, index) => (
                        <SortableBlockItem
                            key={`${editorKey}-${block.id}`}
                            block={block}
                            label={
                                blockTypes.find((t) => t.type === block.type)
                                    ?.label || block.type
                            }
                            editorKey={editorKey}
                            onRemove={() => removeBlock(block.id)}
                            onUpdateData={(key, val) =>
                                updateBlockData(block.id, key, val)
                            }
                        />
                    ))}
                </SortableContext>
            </DndContext>

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button type="button" variant="outline" className="w-full">
                        <Plus className="mr-2 h-4 w-4" />
                        Добавить блок
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent className="w-56">
                    {blockTypes.map((type) => (
                        <DropdownMenuItem
                            key={type.type}
                            onClick={() => addBlock(type.type)}
                        >
                            {type.label}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}

function SortableBlockItem({
    block,
    label,
    editorKey,
    onRemove,
    onUpdateData,
}: {
    block: BlockData;
    label: string;
    editorKey?: string;
    onRemove: () => void;
    onUpdateData: (key: string, val: unknown) => void;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: block.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.6 : 1,
    };

    return (
        <Card ref={setNodeRef} style={style} className="overflow-hidden">
            <CardHeader className="flex flex-row items-center justify-between bg-muted/50 py-3">
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        className="cursor-grab touch-none text-muted-foreground active:cursor-grabbing"
                        aria-label="Перетащить блок"
                        {...attributes}
                        {...listeners}
                    >
                        <GripVertical className="h-4 w-4" />
                    </button>
                    <CardTitle className="text-sm font-medium">{label}</CardTitle>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 text-destructive"
                    onClick={onRemove}
                >
                    <Trash className="h-4 w-4" />
                </Button>
            </CardHeader>
            <CardContent className="space-y-4 p-4">
                {block.type === 'text' && (
                    <CpRichTextField
                        editorKey={`${editorKey}-${block.id}`}
                        value={block.data.content || ''}
                        onChange={(val) => onUpdateData('content', val)}
                    />
                )}
                {block.type === 'image_gallery' && (
                    <GalleryEditor
                        images={block.data.images ?? []}
                        onChange={(images) => onUpdateData('images', images)}
                    />
                )}
                {block.type === 'news_list' && (
                    <CpTextField
                        label="Количество материалов"
                        type="number"
                        value={block.data.count || '6'}
                        onChange={(val) => onUpdateData('count', val)}
                    />
                )}
                {block.type === 'map_widget' && (
                    <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <CpTextField
                                label="Широта"
                                value={block.data.lat || ''}
                                onChange={(val) => onUpdateData('lat', val)}
                            />
                            <CpTextField
                                label="Долгота"
                                value={block.data.lng || ''}
                                onChange={(val) => onUpdateData('lng', val)}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <CpTextField
                                label="Масштаб"
                                type="number"
                                value={block.data.zoom || '10'}
                                onChange={(val) => onUpdateData('zoom', val)}
                            />
                            <CpTextField
                                label="Заголовок маркера"
                                value={block.data.title || ''}
                                onChange={(val) => onUpdateData('title', val)}
                            />
                        </div>
                    </div>
                )}
                {block.type === 'cta' && (
                    <div className="grid grid-cols-2 gap-4">
                        <CpTextField
                            label="Текст кнопки"
                            value={block.data.label || ''}
                            onChange={(val) => onUpdateData('label', val)}
                        />
                        <CpTextField
                            label="URL"
                            value={block.data.url || ''}
                            onChange={(val) => onUpdateData('url', val)}
                        />
                    </div>
                )}
                {block.type === 'accordion' && (
                    <AccordionEditor
                        items={block.data.items ?? []}
                        editorKey={`${editorKey}-${block.id}`}
                        onChange={(items) => onUpdateData('items', items)}
                    />
                )}
                {block.type === 'table' && (
                    <TableEditor
                        caption={block.data.caption || ''}
                        headers={block.data.headers ?? []}
                        rows={block.data.rows ?? []}
                        onCaptionChange={(val) => onUpdateData('caption', val)}
                        onHeadersChange={(headers) =>
                            onUpdateData('headers', headers)
                        }
                        onRowsChange={(rows) => onUpdateData('rows', rows)}
                    />
                )}
                {block.type === 'contacts' && (
                    <div className="space-y-4">
                        <CpTextField
                            label="Заголовок"
                            value={block.data.heading || ''}
                            onChange={(val) => onUpdateData('heading', val)}
                        />
                        <CpTextField
                            label="Адрес"
                            value={block.data.address || ''}
                            onChange={(val) => onUpdateData('address', val)}
                        />
                        <div className="grid grid-cols-2 gap-4">
                            <CpTextField
                                label="Телефон"
                                value={block.data.phone || ''}
                                onChange={(val) => onUpdateData('phone', val)}
                            />
                            <CpTextField
                                label="Email"
                                type="email"
                                value={block.data.email || ''}
                                onChange={(val) => onUpdateData('email', val)}
                            />
                        </div>
                        <CpTextField
                            label="Часы работы"
                            value={block.data.hours || ''}
                            onChange={(val) => onUpdateData('hours', val)}
                        />
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

type GalleryImage = { url: string; alt: string; caption: string };

function GalleryEditor({
    images,
    onChange,
}: {
    images: GalleryImage[];
    onChange: (images: GalleryImage[]) => void;
}) {
    const items = Array.isArray(images) ? images : [];
    const [pickerOpen, setPickerOpen] = useState(false);
    const [search, setSearch] = useState('');
    const { items: libraryItems, loading, page, lastPage, loadMore } = useMediaLibrary({
        enabled: pickerOpen,
        filters: { search, type: 'image' },
        imagesOnly: true,
    });

    const updateImage = (index: number, key: keyof GalleryImage, value: string) => {
        onChange(
            items.map((image, i) => (i === index ? { ...image, [key]: value } : image)),
        );
    };

    const addImage = () => {
        onChange([...items, { url: '', alt: '', caption: '' }]);
    };

    const removeImage = (index: number) => {
        onChange(items.filter((_, i) => i !== index));
    };

    const pickFromLibrary = (item: MediaLibraryItem) => {
        const url = item.original_url ?? item.thumb_url ?? '';

        if (url === '') {
            return;
        }

        onChange([
            ...items,
            {
                url,
                alt: item.alt_text ?? item.name,
                caption: '',
            },
        ]);
        setPickerOpen(false);
    };

    return (
        <div className="space-y-3">
            {items.map((image, index) => (
                <div key={index} className="space-y-2 rounded-lg border p-3">
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium">Image {index + 1}</span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="h-7 w-7 text-destructive"
                            onClick={() => removeImage(index)}
                        >
                            <Trash className="h-3.5 w-3.5" />
                        </Button>
                    </div>
                    {image.url && (
                        <img
                            src={image.url}
                            alt={image.alt || ''}
                            className="h-24 w-full rounded-md object-cover"
                        />
                    )}
                    <CpTextField
                        label="URL"
                        value={image.url}
                        onChange={(val) => updateImage(index, 'url', val)}
                    />
                    <div className="grid grid-cols-2 gap-3">
                        <CpTextField
                            label="Alt Text"
                            value={image.alt}
                            onChange={(val) => updateImage(index, 'alt', val)}
                        />
                        <CpTextField
                            label="Caption"
                            value={image.caption}
                            onChange={(val) => updateImage(index, 'caption', val)}
                        />
                    </div>
                </div>
            ))}
            <div className="flex flex-wrap gap-2">
                <Button type="button" variant="outline" size="sm" onClick={addImage}>
                    <Plus className="mr-1 h-3.5 w-3.5" />
                    Add Image
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setPickerOpen(true)}
                >
                    <ImageIcon className="mr-1 h-3.5 w-3.5" />
                    Из медиатеки
                </Button>
            </div>

            <CpStack
                open={pickerOpen}
                onOpenChange={setPickerOpen}
                title="Выбор изображения"
            >
                <div className="space-y-3">
                    <MediaBrowserFilters
                        search={search}
                        type="image"
                        onSearchChange={setSearch}
                        onTypeChange={() => undefined}
                        hideTypeFilter
                    />
                    <MediaBrowserGrid
                        items={libraryItems}
                        loading={loading}
                        onPick={pickFromLibrary}
                    />
                    {page < lastPage && (
                        <div className="flex justify-center">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={loading}
                                onClick={loadMore}
                            >
                                {loading ? 'Загрузка…' : 'Показать ещё'}
                            </Button>
                        </div>
                    )}
                </div>
            </CpStack>
        </div>
    );
}

type AccordionItem = { title: string; content: string };

function AccordionEditor({
    items,
    editorKey,
    onChange,
}: {
    items: AccordionItem[];
    editorKey: string;
    onChange: (items: AccordionItem[]) => void;
}) {
    const rows = Array.isArray(items) ? items : [];

    const updateItem = (index: number, key: keyof AccordionItem, value: string) => {
        onChange(rows.map((item, i) => (i === index ? { ...item, [key]: value } : item)));
    };

    const addItem = () => {
        onChange([...rows, { title: '', content: '' }]);
    };

    const removeItem = (index: number) => {
        onChange(rows.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-3">
            {rows.map((item, index) => (
                <div key={index} className="space-y-2 rounded-lg border p-3">
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium">Item {index + 1}</span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="h-7 w-7 text-destructive"
                            onClick={() => removeItem(index)}
                        >
                            <Trash className="h-3.5 w-3.5" />
                        </Button>
                    </div>
                    <CpTextField
                        label="Title"
                        value={item.title}
                        onChange={(val) => updateItem(index, 'title', val)}
                    />
                    <CpRichTextField
                        editorKey={`${editorKey}-accordion-${index}`}
                        label="Content"
                        value={item.content}
                        onChange={(val) => updateItem(index, 'content', val)}
                    />
                </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={addItem}>
                <Plus className="mr-1 h-3.5 w-3.5" />
                Add Item
            </Button>
        </div>
    );
}

function TableEditor({
    caption,
    headers,
    rows,
    onCaptionChange,
    onHeadersChange,
    onRowsChange,
}: {
    caption: string;
    headers: string[];
    rows: string[][];
    onCaptionChange: (value: string) => void;
    onHeadersChange: (headers: string[]) => void;
    onRowsChange: (rows: string[][]) => void;
}) {
    const headerList = Array.isArray(headers) && headers.length > 0 ? headers : ['Column 1'];
    const rowList = Array.isArray(rows) ? rows : [];

    const updateHeader = (index: number, value: string) => {
        const next = [...headerList];
        next[index] = value;
        onHeadersChange(next);
    };

    const addColumn = () => {
        onHeadersChange([...headerList, `Column ${headerList.length + 1}`]);
        onRowsChange(rowList.map((row) => [...row, '']));
    };

    const removeColumn = (index: number) => {
        if (headerList.length <= 1) return;
        onHeadersChange(headerList.filter((_, i) => i !== index));
        onRowsChange(rowList.map((row) => row.filter((_, i) => i !== index)));
    };

    const updateCell = (rowIndex: number, colIndex: number, value: string) => {
        onRowsChange(
            rowList.map((row, ri) =>
                ri === rowIndex
                    ? row.map((cell, ci) => (ci === colIndex ? value : cell))
                    : row,
            ),
        );
    };

    const addRow = () => {
        onRowsChange([...rowList, headerList.map(() => '')]);
    };

    const removeRow = (index: number) => {
        onRowsChange(rowList.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-4">
            <CpTextField label="Caption" value={caption} onChange={onCaptionChange} />
            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <span className="text-sm font-medium">Headers</span>
                    <Button type="button" variant="outline" size="sm" onClick={addColumn}>
                        Add Column
                    </Button>
                </div>
                <div className="grid gap-2" style={{ gridTemplateColumns: `repeat(${headerList.length}, 1fr)` }}>
                    {headerList.map((header, index) => (
                        <div key={index} className="flex gap-1">
                            <CpTextField
                                value={header}
                                onChange={(val) => updateHeader(index, val)}
                            />
                            {headerList.length > 1 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="h-9 w-9 shrink-0 text-destructive"
                                    onClick={() => removeColumn(index)}
                                >
                                    <Trash className="h-3.5 w-3.5" />
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            </div>
            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <span className="text-sm font-medium">Rows</span>
                    <Button type="button" variant="outline" size="sm" onClick={addRow}>
                        Add Row
                    </Button>
                </div>
                {rowList.map((row, rowIndex) => (
                    <div key={rowIndex} className="flex items-start gap-2">
                        <div
                            className="grid flex-1 gap-2"
                            style={{ gridTemplateColumns: `repeat(${headerList.length}, 1fr)` }}
                        >
                            {headerList.map((_, colIndex) => (
                                <CpTextField
                                    key={colIndex}
                                    value={row[colIndex] ?? ''}
                                    onChange={(val) => updateCell(rowIndex, colIndex, val)}
                                />
                            ))}
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="h-9 w-9 shrink-0 text-destructive"
                            onClick={() => removeRow(rowIndex)}
                        >
                            <Trash className="h-3.5 w-3.5" />
                        </Button>
                    </div>
                ))}
            </div>
        </div>
    );
}

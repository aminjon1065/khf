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

export type BlockData = {
    id: string;
    type: string;
    data: Record<string, any>;
};

type CpBlocksFieldProps = {
    value: BlockData[];
    onChange: (blocks: BlockData[]) => void;
    editorKey?: string;
};

const BLOCK_TYPES = [
    { type: 'text', label: 'Rich Text' },
    { type: 'image_gallery', label: 'Image Gallery' },
    { type: 'news_list', label: 'News List' },
    { type: 'map_widget', label: 'Map Widget' },
    { type: 'cta', label: 'Call to Action' },
    { type: 'accordion', label: 'Accordion' },
    { type: 'table', label: 'Table' },
    { type: 'contacts', label: 'Contacts' },
] as const;

const DEFAULT_BLOCK_DATA: Record<string, Record<string, unknown>> = {
    text: { content: '' },
    image_gallery: { images: [{ url: '', alt: '', caption: '' }] },
    news_list: { count: '6' },
    map_widget: { lat: '38.5598', lng: '68.7870', zoom: '10', title: '' },
    cta: { label: '', url: '' },
    accordion: { items: [{ title: '', content: '' }] },
    table: { caption: '', headers: ['Column 1', 'Column 2'], rows: [['', '']] },
    contacts: { heading: '', address: '', phone: '', email: '', hours: '' },
};

export function CpBlocksField({ value = [], onChange, editorKey }: CpBlocksFieldProps) {
    const blocks = Array.isArray(value) ? value : [];

    const addBlock = (type: string) => {
        const newBlock: BlockData = {
            id: Math.random().toString(36).substring(2, 9),
            type,
            data: structuredClone(DEFAULT_BLOCK_DATA[type] ?? {}),
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

    const moveBlock = (index: number, direction: 'up' | 'down') => {
        if (direction === 'up' && index === 0) return;
        if (direction === 'down' && index === blocks.length - 1) return;

        const newBlocks = [...blocks];
        const swapIndex = direction === 'up' ? index - 1 : index + 1;
        [newBlocks[index], newBlocks[swapIndex]] = [newBlocks[swapIndex], newBlocks[index]];
        onChange(newBlocks);
    };

    return (
        <div className="space-y-4">
            {blocks.map((block, index) => (
                <Card key={`${editorKey}-${block.id}`} className="overflow-hidden">
                    <CardHeader className="bg-muted/50 py-3 flex flex-row items-center justify-between">
                        <div className="flex items-center gap-2">
                            <GripVertical className="h-4 w-4 text-muted-foreground cursor-move" />
                            <CardTitle className="text-sm font-medium">
                                {BLOCK_TYPES.find((t) => t.type === block.type)?.label || block.type}
                            </CardTitle>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => moveBlock(index, 'up')}
                                disabled={index === 0}
                            >
                                ↑
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => moveBlock(index, 'down')}
                                disabled={index === blocks.length - 1}
                            >
                                ↓
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8 text-destructive"
                                onClick={() => removeBlock(block.id)}
                            >
                                <Trash className="h-4 w-4" />
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 space-y-4">
                        {block.type === 'text' && (
                            <CpRichTextField
                                editorKey={`${editorKey}-${block.id}`}
                                value={block.data.content || ''}
                                onChange={(val) => updateBlockData(block.id, 'content', val)}
                            />
                        )}
                        {block.type === 'image_gallery' && (
                            <GalleryEditor
                                images={block.data.images ?? []}
                                onChange={(images) => updateBlockData(block.id, 'images', images)}
                            />
                        )}
                        {block.type === 'news_list' && (
                            <CpTextField
                                label="Number of Posts"
                                type="number"
                                value={block.data.count || '6'}
                                onChange={(val) => updateBlockData(block.id, 'count', val)}
                            />
                        )}
                        {block.type === 'map_widget' && (
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <CpTextField
                                        label="Latitude"
                                        value={block.data.lat || ''}
                                        onChange={(val) => updateBlockData(block.id, 'lat', val)}
                                    />
                                    <CpTextField
                                        label="Longitude"
                                        value={block.data.lng || ''}
                                        onChange={(val) => updateBlockData(block.id, 'lng', val)}
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <CpTextField
                                        label="Zoom"
                                        type="number"
                                        value={block.data.zoom || '10'}
                                        onChange={(val) => updateBlockData(block.id, 'zoom', val)}
                                    />
                                    <CpTextField
                                        label="Marker Title"
                                        value={block.data.title || ''}
                                        onChange={(val) => updateBlockData(block.id, 'title', val)}
                                    />
                                </div>
                            </div>
                        )}
                        {block.type === 'cta' && (
                            <div className="grid grid-cols-2 gap-4">
                                <CpTextField
                                    label="Button Label"
                                    value={block.data.label || ''}
                                    onChange={(val) => updateBlockData(block.id, 'label', val)}
                                />
                                <CpTextField
                                    label="URL"
                                    value={block.data.url || ''}
                                    onChange={(val) => updateBlockData(block.id, 'url', val)}
                                />
                            </div>
                        )}
                        {block.type === 'accordion' && (
                            <AccordionEditor
                                items={block.data.items ?? []}
                                editorKey={`${editorKey}-${block.id}`}
                                onChange={(items) => updateBlockData(block.id, 'items', items)}
                            />
                        )}
                        {block.type === 'table' && (
                            <TableEditor
                                caption={block.data.caption || ''}
                                headers={block.data.headers ?? []}
                                rows={block.data.rows ?? []}
                                onCaptionChange={(val) => updateBlockData(block.id, 'caption', val)}
                                onHeadersChange={(headers) => updateBlockData(block.id, 'headers', headers)}
                                onRowsChange={(rows) => updateBlockData(block.id, 'rows', rows)}
                            />
                        )}
                        {block.type === 'contacts' && (
                            <div className="space-y-4">
                                <CpTextField
                                    label="Heading"
                                    value={block.data.heading || ''}
                                    onChange={(val) => updateBlockData(block.id, 'heading', val)}
                                />
                                <CpTextField
                                    label="Address"
                                    value={block.data.address || ''}
                                    onChange={(val) => updateBlockData(block.id, 'address', val)}
                                />
                                <div className="grid grid-cols-2 gap-4">
                                    <CpTextField
                                        label="Phone"
                                        value={block.data.phone || ''}
                                        onChange={(val) => updateBlockData(block.id, 'phone', val)}
                                    />
                                    <CpTextField
                                        label="Email"
                                        type="email"
                                        value={block.data.email || ''}
                                        onChange={(val) => updateBlockData(block.id, 'email', val)}
                                    />
                                </div>
                                <CpTextField
                                    label="Working Hours"
                                    value={block.data.hours || ''}
                                    onChange={(val) => updateBlockData(block.id, 'hours', val)}
                                />
                            </div>
                        )}
                    </CardContent>
                </Card>
            ))}

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button type="button" variant="outline" className="w-full">
                        <Plus className="mr-2 h-4 w-4" />
                        Add Block
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent className="w-56">
                    {BLOCK_TYPES.map((type) => (
                        <DropdownMenuItem key={type.type} onClick={() => addBlock(type.type)}>
                            {type.label}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
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

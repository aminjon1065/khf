import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { CpTextField, CpRichTextField } from '@/components/admin/cp/fields';
import { Plus, Trash, GripVertical } from 'lucide-react';

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
    { type: 'news_list', label: 'News List' },
    { type: 'map_widget', label: 'Map Widget' },
    { type: 'cta', label: 'Call to Action' },
];

export function CpBlocksField({ value = [], onChange, editorKey }: CpBlocksFieldProps) {
    const blocks = Array.isArray(value) ? value : [];

    const addBlock = (type: string) => {
        const newBlock: BlockData = {
            id: Math.random().toString(36).substring(2, 9),
            type,
            data: {},
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
                        {block.type === 'news_list' && (
                            <CpTextField
                                label="Number of Posts"
                                type="number"
                                value={block.data.count || '6'}
                                onChange={(val) => updateBlockData(block.id, 'count', val)}
                            />
                        )}
                        {block.type === 'map_widget' && (
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

import {
    ChevronDown,
    ChevronRight,
    Folder,
    FolderPlus,
    Lock,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import {
    destroy as destroyFolder,
    store as storeFolder,
} from '@/routes/admin/media/folders';

export type MediaFolderNode = {
    id: number;
    name: string;
    container: 'public' | 'private';
    container_label: string;
    parent_id: number | null;
    files_count: number;
    children: MediaFolderNode[];
};

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

function FolderBranch({
    node,
    depth,
    activeFolderId,
    expanded,
    onToggle,
    onSelect,
    onDelete,
}: {
    node: MediaFolderNode;
    depth: number;
    activeFolderId: string;
    expanded: Set<number>;
    onToggle: (id: number) => void;
    onSelect: (folderId: string) => void;
    onDelete: (id: number) => void;
}) {
    const isOpen = expanded.has(node.id);
    const isActive = activeFolderId === String(node.id);
    const hasChildren = node.children.length > 0;

    return (
        <div>
            <div
                className={cn(
                    'group flex items-center gap-1 rounded-md pr-1 text-sm transition-colors',
                    isActive
                        ? 'bg-primary/10 text-primary'
                        : 'hover:bg-muted/60',
                )}
                style={{ paddingLeft: `${depth * 12 + 4}px` }}
            >
                <button
                    type="button"
                    className="flex size-7 shrink-0 items-center justify-center rounded-sm text-muted-foreground hover:text-foreground"
                    aria-label={isOpen ? 'Свернуть' : 'Развернуть'}
                    onClick={() => onToggle(node.id)}
                >
                    {hasChildren ? (
                        isOpen ? (
                            <ChevronDown className="size-4" />
                        ) : (
                            <ChevronRight className="size-4" />
                        )
                    ) : (
                        <span className="size-4" />
                    )}
                </button>
                <button
                    type="button"
                    className="flex min-w-0 flex-1 items-center gap-2 py-1.5 text-left"
                    onClick={() => onSelect(String(node.id))}
                >
                    {node.container === 'private' ? (
                        <Lock className="size-4 shrink-0 text-muted-foreground" />
                    ) : (
                        <Folder className="size-4 shrink-0 text-muted-foreground" />
                    )}
                    <span className="truncate">{node.name}</span>
                    <span className="ml-auto shrink-0 text-xs text-muted-foreground">
                        {node.files_count}
                    </span>
                </button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-7 opacity-0 group-hover:opacity-100"
                    aria-label="Удалить папку"
                    onClick={() => onDelete(node.id)}
                >
                    <Trash2 className="size-3.5" />
                </Button>
            </div>

            {isOpen &&
                node.children.map((child) => (
                    <FolderBranch
                        key={child.id}
                        node={child}
                        depth={depth + 1}
                        activeFolderId={activeFolderId}
                        expanded={expanded}
                        onToggle={onToggle}
                        onSelect={onSelect}
                        onDelete={onDelete}
                    />
                ))}
        </div>
    );
}

export function MediaFolderTree({
    folders,
    activeFolderId,
    onSelect,
    onFoldersChange,
}: {
    folders: MediaFolderNode[];
    activeFolderId: string;
    onSelect: (folderId: string) => void;
    onFoldersChange: (folders: MediaFolderNode[]) => void;
}) {
    const [expanded, setExpanded] = useState<Set<number>>(new Set());
    const [createOpen, setCreateOpen] = useState(false);
    const [name, setName] = useState('');
    const [container, setContainer] = useState<'public' | 'private'>('public');
    const [parentId, setParentId] = useState<string>('root');
    const [saving, setSaving] = useState(false);

    const toggle = (id: number) => {
        setExpanded((current) => {
            const next = new Set(current);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });
    };

    const flatFolders = (nodes: MediaFolderNode[], depth = 0): Array<{ id: number; label: string }> => {
        return nodes.flatMap((node) => [
            { id: node.id, label: `${'— '.repeat(depth)}${node.name}` },
            ...flatFolders(node.children, depth + 1),
        ]);
    };

    const createFolder = async () => {
        if (name.trim() === '') {
            return;
        }

        setSaving(true);

        try {
            const response = await fetch(storeFolder().url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    name: name.trim(),
                    parent_id: parentId === 'root' ? null : Number(parentId),
                    container: parentId === 'root' ? container : null,
                }),
            });

            if (!response.ok) {
                throw new Error('create failed');
            }

            const payload = await response.json();
            onFoldersChange(payload.data ?? []);
            setCreateOpen(false);
            setName('');
            setParentId('root');
            setContainer('public');
            toast.success('Папка создана');
        } catch {
            toast.error('Не удалось создать папку');
        } finally {
            setSaving(false);
        }
    };

    const deleteFolder = async (id: number) => {
        if (!confirm('Удалить папку? Файлы будут перемещены на уровень выше.')) {
            return;
        }

        try {
            const response = await fetch(destroyFolder(id).url, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('delete failed');
            }

            const payload = await response.json();
            onFoldersChange(payload.data ?? []);

            if (activeFolderId === String(id)) {
                onSelect('all');
            }

            toast.success('Папка удалена');
        } catch {
            toast.error('Не удалось удалить папку');
        }
    };

    return (
        <div className="space-y-3 rounded-lg border border-border bg-card p-3 shadow-sm">
            <div className="flex items-center justify-between gap-2">
                <h2 className="text-sm font-semibold">Папки</h2>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    className="size-8"
                    aria-label="Создать папку"
                    onClick={() => setCreateOpen(true)}
                >
                    <FolderPlus className="size-4" />
                </Button>
            </div>

            <div className="space-y-0.5">
                <button
                    type="button"
                    onClick={() => onSelect('all')}
                    className={cn(
                        'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm transition-colors',
                        activeFolderId === 'all'
                            ? 'bg-primary/10 text-primary'
                            : 'hover:bg-muted/60',
                    )}
                >
                    <Folder className="size-4 shrink-0 text-muted-foreground" />
                    Все файлы
                </button>
                <button
                    type="button"
                    onClick={() => onSelect('0')}
                    className={cn(
                        'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm transition-colors',
                        activeFolderId === '0'
                            ? 'bg-primary/10 text-primary'
                            : 'hover:bg-muted/60',
                    )}
                >
                    <Folder className="size-4 shrink-0 text-muted-foreground" />
                    Без папки
                </button>

                {folders.map((folder) => (
                    <FolderBranch
                        key={folder.id}
                        node={folder}
                        depth={0}
                        activeFolderId={activeFolderId}
                        expanded={expanded}
                        onToggle={toggle}
                        onSelect={onSelect}
                        onDelete={deleteFolder}
                    />
                ))}
            </div>

            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Новая папка</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="folder-name">Название</Label>
                            <Input
                                id="folder-name"
                                value={name}
                                onChange={(event) => setName(event.target.value)}
                                placeholder="Например, Баннеры"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Родительская папка</Label>
                            <Select value={parentId} onValueChange={setParentId}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="root">Корень</SelectItem>
                                    {flatFolders(folders).map((folder) => (
                                        <SelectItem
                                            key={folder.id}
                                            value={String(folder.id)}
                                        >
                                            {folder.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        {parentId === 'root' && (
                            <div className="space-y-2">
                                <Label>Контейнер</Label>
                                <Select
                                    value={container}
                                    onValueChange={(value) =>
                                        setContainer(value as 'public' | 'private')
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="public">
                                            Публичные
                                        </SelectItem>
                                        <SelectItem value="private">
                                            Приватные
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={createFolder}
                            disabled={saving || name.trim() === ''}
                        >
                            Создать
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

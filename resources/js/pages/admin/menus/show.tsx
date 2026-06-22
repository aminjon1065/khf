import { Link, router } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Edit2, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import AdminLayout from '@/layouts/admin/admin-layout';
import { index, show, reorder } from '@/routes/admin/menus';
import { destroy } from '@/routes/admin/menus/items';
import MenuItemModal from './menu-item-modal';

export default function MenuShow({ menu, items, locales, defaultLocale }: any) {
    const { t } = useTranslations();
    const [modalOpen, setModalOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<any>(null);
    const [parentForNew, setParentForNew] = useState<number | null>(null);

    const handleAdd = (parentId: number | null = null) => {
        setParentForNew(parentId);
        setEditingItem(null);
        setModalOpen(true);
    };

    const handleEdit = (item: any) => {
        setParentForNew(null);
        setEditingItem(item);
        setModalOpen(true);
    };

    const handleDelete = (item: any) => {
        if (confirm(t('actions.confirm_delete'))) {
            destroy({ menu: menu.id, item: item.id }).delete({
                preserveScroll: true,
            });
        }
    };

    const handleMove = (item: any, direction: 'up' | 'down', siblings: any[]) => {
        const currentIndex = siblings.findIndex((s) => s.id === item.id);
        if (direction === 'up' && currentIndex > 0) {
            // Swap with previous
            swapAndSave(siblings, currentIndex, currentIndex - 1);
        } else if (direction === 'down' && currentIndex < siblings.length - 1) {
            // Swap with next
            swapAndSave(siblings, currentIndex, currentIndex + 1);
        }
    };

    const swapAndSave = (siblings: any[], idxA: number, idxB: number) => {
        const newArray = [...siblings];
        const temp = newArray[idxA];
        newArray[idxA] = newArray[idxB];
        newArray[idxB] = temp;

        // Reassign sort orders based on array index
        const payload = newArray.map((s, idx) => ({
            id: s.id,
            parent_id: s.parent_id,
            sort_order: idx + 1,
        }));

        reorder({ menu: menu.id }).post({ items: payload }, { preserveScroll: true });
    };

    const renderItems = (nodes: any[], level = 0) => {
        if (!nodes || nodes.length === 0) {
            return level === 0 ? (
                <div className="p-8 text-center text-sm text-muted-foreground border rounded-md">
                    {t('modules.menus.empty')}
                </div>
            ) : null;
        }

        return (
            <div className={`flex flex-col gap-2 ${level > 0 ? 'ml-6 mt-2 border-l pl-4' : ''}`}>
                {nodes.map((node, index) => (
                    <div key={node.id} className="flex flex-col gap-2">
                        <div className="flex items-center justify-between rounded-md border bg-card p-3 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="font-medium">
                                    {node.translations[defaultLocale]?.title || '—'}
                                </div>
                                {node.url && (
                                    <div className="text-xs text-muted-foreground bg-muted px-2 py-0.5 rounded">
                                        {node.url}
                                    </div>
                                )}
                            </div>
                            <div className="flex items-center gap-1">
                                <div className="flex flex-col gap-0.5 mr-2">
                                    <button
                                        type="button"
                                        onClick={() => handleMove(node, 'up', nodes)}
                                        disabled={index === 0}
                                        className="text-muted-foreground hover:text-foreground disabled:opacity-30 disabled:hover:text-muted-foreground"
                                    >
                                        <ChevronUp className="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => handleMove(node, 'down', nodes)}
                                        disabled={index === nodes.length - 1}
                                        className="text-muted-foreground hover:text-foreground disabled:opacity-30 disabled:hover:text-muted-foreground"
                                    >
                                        <ChevronDown className="size-4" />
                                    </button>
                                </div>
                                <Button variant="ghost" size="icon" onClick={() => handleAdd(node.id)} title={t('actions.add_child')}>
                                    <Plus className="size-4" />
                                </Button>
                                <Button variant="ghost" size="icon" onClick={() => handleEdit(node)}>
                                    <Edit2 className="size-4" />
                                </Button>
                                <Button variant="ghost" size="icon" className="text-destructive" onClick={() => handleDelete(node)}>
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        </div>
                        {node.children && node.children.length > 0 && renderItems(node.children, level + 1)}
                    </div>
                ))}
            </div>
        );
    };

    return (
        <AdminLayout
            title={menu.name}
            breadcrumbs={
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <BreadcrumbLink href={index().url}>{t('modules.menus.title')}</BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbPage>{menu.name}</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            }
        >
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{menu.name}</h1>
                        <p className="text-sm text-muted-foreground">{t('modules.menus.builder_description')}</p>
                    </div>
                    <Button onClick={() => handleAdd(null)}>
                        <Plus className="mr-2 size-4" />
                        {t('actions.create')}
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('modules.menus.items')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {renderItems(items)}
                    </CardContent>
                </Card>

                {modalOpen && (
                    <MenuItemModal
                        isOpen={modalOpen}
                        onClose={() => setModalOpen(false)}
                        menuId={menu.id}
                        item={editingItem}
                        parentId={parentForNew}
                        locales={locales}
                        defaultLocale={defaultLocale}
                    />
                )}
            </div>
        </AdminLayout>
    );
}

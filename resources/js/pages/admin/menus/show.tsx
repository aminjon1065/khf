import { Head, router } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Edit2, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes/admin';
import { index, reorder } from '@/routes/admin/menus';
import { destroy } from '@/routes/admin/menus/items';
import MenuItemModal from './menu-item-modal';
import { MenuMegaPreview } from './menu-mega-preview';

export default function MenuShow({ menu, items, locales, defaultLocale, linkSections, linkPages, linkCollectionEntries }: any) {
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
        if (confirm('Удалить пункт меню?')) {
            router.delete(destroy({ menu: menu.id, item: item.id }).url, {
                preserveScroll: true,
            });
        }
    };

    const handleMove = (item: any, direction: 'up' | 'down', siblings: any[]) => {
        const currentIndex = siblings.findIndex((s) => s.id === item.id);
        if (direction === 'up' && currentIndex > 0) {
            swapAndSave(siblings, currentIndex, currentIndex - 1);
        } else if (direction === 'down' && currentIndex < siblings.length - 1) {
            swapAndSave(siblings, currentIndex, currentIndex + 1);
        }
    };

    const swapAndSave = (siblings: any[], idxA: number, idxB: number) => {
        const newArray = [...siblings];
        const temp = newArray[idxA];
        newArray[idxA] = newArray[idxB];
        newArray[idxB] = temp;

        const payload = newArray.map((s, idx) => ({
            id: s.id,
            parent_id: s.parent_id,
            sort_order: idx + 1,
        }));

        router.post(reorder({ menu: menu.id }).url, { items: payload }, { preserveScroll: true });
    };

    const renderItems = (nodes: any[], level = 0) => {
        if (!nodes || nodes.length === 0) {
            return level === 0 ? (
                <div className="rounded-md border p-8 text-center text-sm text-muted-foreground">
                    Пункты меню ещё не добавлены
                </div>
            ) : null;
        }

        return (
            <div className={`flex flex-col gap-2 ${level > 0 ? 'mt-2 ml-6 border-l pl-4' : ''}`}>
                {nodes.map((node, index) => (
                    <div key={node.id} className="flex flex-col gap-2">
                        <div className="flex items-center justify-between rounded-md border bg-card p-3 shadow-sm">
                            <div className="flex items-center gap-3">
                                <div className="font-medium">
                                    {node.translations[defaultLocale]?.title || '—'}
                                </div>
                                <div className="flex gap-1">
                                    {locales.map((locale: { code: string }) => {
                                        const hasTranslation = (node.locales ?? []).includes(locale.code);

                                        return (
                                            <span
                                                key={locale.code}
                                                className={`rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase ${
                                                    hasTranslation
                                                        ? 'bg-primary/10 text-primary'
                                                        : 'bg-muted text-muted-foreground'
                                                }`}
                                            >
                                                {locale.code}
                                            </span>
                                        );
                                    })}
                                </div>
                                {node.url && (
                                    <div className="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                        {node.url}
                                    </div>
                                )}
                                {node.route && !node.url && (
                                    <div className="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                        {node.route}
                                    </div>
                                )}
                                {node.preview_url && (
                                    <div className="max-w-[220px] truncate rounded bg-primary/5 px-2 py-0.5 text-xs text-primary">
                                        {node.preview_url}
                                    </div>
                                )}
                            </div>
                            <div className="flex items-center gap-1">
                                <div className="mr-2 flex flex-col gap-0.5">
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
                                <Button variant="ghost" size="icon" onClick={() => handleAdd(node.id)} title="Добавить подпункт">
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
        <>
            <Head title={menu.name} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{menu.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            {menu.location_label ?? menu.location} · добавляйте пункты, вложенность и
                            порядок отображения на сайте
                        </p>
                    </div>
                    <Button onClick={() => handleAdd(null)}>
                        <Plus className="mr-2 size-4" />
                        Добавить пункт
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Превью навигации</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <MenuMegaPreview
                            items={items}
                            defaultLocale={defaultLocale}
                            location={menu.location}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Пункты меню</CardTitle>
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
                        linkSections={linkSections}
                        linkPages={linkPages}
                        linkCollectionEntries={linkCollectionEntries}
                    />
                )}
            </div>
        </>
    );
}

MenuShow.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Меню', href: index() },
    ],
};

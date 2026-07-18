import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { store, update } from '@/routes/admin/menus/items';

type LinkSection = { value: string; label: string; group: string };
type LinkPage = {
    id: number;
    title: string;
    is_home?: boolean;
    depth?: number;
};
type CollectionEntryGroup = {
    handle: string;
    label: string;
    entries: Array<{ id: number; title: string }>;
};

type LinkType = 'section' | 'page' | 'entry' | 'external';

function resolveLinkType(item: any): LinkType {
    if (item?.url && /^https?:\/\//i.test(item.url)) {
        return 'external';
    }

    if (item?.route?.startsWith('entry.')) {
        return 'entry';
    }

    if (item?.route?.startsWith('page.')) {
        return 'page';
    }

    if (item?.route) {
        return 'section';
    }

    if (item?.url) {
        return 'external';
    }

    return 'section';
}

function resolvePageId(item: any): string {
    if (item?.route?.startsWith('page.')) {
        return item.route.replace('page.', '');
    }

    return '';
}

function resolveEntryLink(item: any): { handle: string; id: string } {
    if (!item?.route?.startsWith('entry.')) {
        return { handle: '', id: '' };
    }

    const [, handle, id] = item.route.split('.');

    return { handle: handle ?? '', id: id ?? '' };
}

export default function MenuItemModal({
    isOpen,
    onClose,
    menuId,
    item,
    parentId,
    locales,
    defaultLocale,
    linkSections,
    linkPages,
    linkCollectionEntries,
}: {
    isOpen: boolean;
    onClose: () => void;
    menuId: number;
    item: any;
    parentId: number | null;
    locales: any[];
    defaultLocale: string;
    linkSections: LinkSection[];
    linkPages: LinkPage[];
    linkCollectionEntries: CollectionEntryGroup[];
}) {
    const isEditing = !!item;

    const initialTranslations = locales.reduce(
        (acc, locale) => {
            acc[locale.code] = {
                title: item?.translations?.[locale.code]?.title || '',
            };

            return acc;
        },
        {} as Record<string, { title: string }>,
    );

    const [linkType, setLinkType] = useState<LinkType>(resolveLinkType(item));
    const [sectionRoute, setSectionRoute] = useState(
        item?.route && !item.route.startsWith('page.') ? item.route : 'welcome',
    );
    const [pageId, setPageId] = useState(resolvePageId(item));
    const [externalUrl, setExternalUrl] = useState(
        item?.url && /^https?:\/\//i.test(item.url) ? item.url : '',
    );

    const initialEntry = resolveEntryLink(item);
    const [entryCollection, setEntryCollection] = useState(
        initialEntry.handle || linkCollectionEntries[0]?.handle || '',
    );
    const [entryId, setEntryId] = useState(initialEntry.id);

    const { data, setData, processing, errors, reset } = useForm({
        parent_id: parentId || item?.parent_id || null,
        url: '',
        route: '',
        target: item?.target || '_self',
        translations: initialTranslations,
    });
    const fieldErrors = errors as Record<string, string | undefined>;

    const sectionGroups = useMemo(() => {
        return linkSections.reduce<Record<string, LinkSection[]>>(
            (groups, section) => {
                (groups[section.group] ??= []).push(section);

                return groups;
            },
            {},
        );
    }, [linkSections]);

    const selectedCollection = useMemo(
        () =>
            linkCollectionEntries.find(
                (group) => group.handle === entryCollection,
            ),
        [entryCollection, linkCollectionEntries],
    );

    const applyLinkToForm = (): { url: string; route: string } => {
        if (linkType === 'external') {
            return { url: externalUrl.trim(), route: '' };
        }

        if (linkType === 'page') {
            return { url: '', route: pageId ? `page.${pageId}` : '' };
        }

        if (linkType === 'entry') {
            return {
                url: '',
                route:
                    entryCollection && entryId
                        ? `entry.${entryCollection}.${entryId}`
                        : '',
            };
        }

        return { url: '', route: sectionRoute };
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        const link = applyLinkToForm();
        const payload = {
            parent_id: data.parent_id,
            url: link.url,
            route: link.route,
            target: data.target,
            translations: data.translations,
        };

        const url = isEditing
            ? update({ menu: menuId, item: item.id }).url
            : store({ menu: menuId }).url;

        router.visit(url, {
            method: isEditing ? 'put' : 'post',
            data: payload,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-[560px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEditing
                                ? 'Редактировать пункт'
                                : 'Новый пункт меню'}
                        </DialogTitle>
                        <DialogDescription>
                            Укажите название на языках и выберите, куда ведёт
                            пункт меню.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <Tabs defaultValue={defaultLocale} className="w-full">
                            <TabsList className="mb-4 flex w-full">
                                {locales.map((locale) => (
                                    <TabsTrigger
                                        key={locale.code}
                                        value={locale.code}
                                        className="flex-1"
                                    >
                                        {locale.native_name}
                                    </TabsTrigger>
                                ))}
                            </TabsList>

                            {locales.map((locale) => (
                                <TabsContent
                                    key={locale.code}
                                    value={locale.code}
                                    className="space-y-4"
                                >
                                    <div className="space-y-2">
                                        <Label htmlFor={`title-${locale.code}`}>
                                            Название (
                                            {locale.code.toUpperCase()})
                                            {locale.code === defaultLocale && (
                                                <span className="text-destructive">
                                                    {' '}
                                                    *
                                                </span>
                                            )}
                                        </Label>
                                        <Input
                                            id={`title-${locale.code}`}
                                            value={
                                                data.translations[locale.code]
                                                    .title
                                            }
                                            onChange={(e) =>
                                                setData('translations', {
                                                    ...data.translations,
                                                    [locale.code]: {
                                                        ...data.translations[
                                                            locale.code
                                                        ],
                                                        title: e.target.value,
                                                    },
                                                })
                                            }
                                        />
                                        {fieldErrors[
                                            `translations.${locale.code}.title`
                                        ] && (
                                            <p className="text-xs text-destructive">
                                                {
                                                    fieldErrors[
                                                        `translations.${locale.code}.title`
                                                    ]
                                                }
                                            </p>
                                        )}
                                    </div>
                                </TabsContent>
                            ))}
                        </Tabs>

                        <div className="space-y-3 rounded-md border border-border p-3">
                            <div className="space-y-2">
                                <Label>Куда ведёт ссылка</Label>
                                <Select
                                    value={linkType}
                                    onValueChange={(value) =>
                                        setLinkType(value as LinkType)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="section">
                                            Раздел сайта
                                        </SelectItem>
                                        <SelectItem value="page">
                                            CMS-страница
                                        </SelectItem>
                                        <SelectItem value="entry">
                                            Запись коллекции
                                        </SelectItem>
                                        <SelectItem value="external">
                                            Внешняя ссылка
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {linkType === 'section' && (
                                <div className="space-y-2">
                                    <Label>Раздел</Label>
                                    <Select
                                        value={sectionRoute}
                                        onValueChange={setSectionRoute}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Выберите раздел" />
                                        </SelectTrigger>
                                        <SelectContent className="max-h-72">
                                            {Object.entries(sectionGroups).map(
                                                ([group, sections]) => (
                                                    <SelectGroup key={group}>
                                                        <SelectLabel>
                                                            {group}
                                                        </SelectLabel>
                                                        {sections.map(
                                                            (section) => (
                                                                <SelectItem
                                                                    key={
                                                                        section.value
                                                                    }
                                                                    value={
                                                                        section.value
                                                                    }
                                                                >
                                                                    {
                                                                        section.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectGroup>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-xs text-muted-foreground">
                                        Ссылка автоматически подставится с
                                        учётом языка посетителя.
                                    </p>
                                </div>
                            )}

                            {linkType === 'page' && (
                                <div className="space-y-2">
                                    <Label>Страница</Label>
                                    <Select
                                        value={pageId || 'none'}
                                        onValueChange={(v) =>
                                            setPageId(v === 'none' ? '' : v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Выберите страницу" />
                                        </SelectTrigger>
                                        <SelectContent className="max-h-72">
                                            <SelectItem value="none">
                                                — Не выбрано —
                                            </SelectItem>
                                            {linkPages.map((page) => (
                                                <SelectItem
                                                    key={page.id}
                                                    value={String(page.id)}
                                                >
                                                    {page.title}
                                                    {page.is_home
                                                        ? ' (главная)'
                                                        : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-xs text-muted-foreground">
                                        Дерево CMS-страниц: вложенность
                                        отображается отступом в списке.
                                    </p>
                                </div>
                            )}

                            {linkType === 'entry' && (
                                <div className="space-y-3">
                                    <div className="space-y-2">
                                        <Label>Коллекция</Label>
                                        <Select
                                            value={entryCollection}
                                            onValueChange={(value) => {
                                                setEntryCollection(value);
                                                setEntryId('');
                                            }}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Выберите коллекцию" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {linkCollectionEntries.map(
                                                    (group) => (
                                                        <SelectItem
                                                            key={group.handle}
                                                            value={group.handle}
                                                        >
                                                            {group.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Запись</Label>
                                        <Select
                                            value={entryId || 'none'}
                                            onValueChange={(v) =>
                                                setEntryId(
                                                    v === 'none' ? '' : v,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Выберите запись" />
                                            </SelectTrigger>
                                            <SelectContent className="max-h-72">
                                                <SelectItem value="none">
                                                    — Не выбрано —
                                                </SelectItem>
                                                {(
                                                    selectedCollection?.entries ??
                                                    []
                                                ).map((entry) => (
                                                    <SelectItem
                                                        key={entry.id}
                                                        value={String(entry.id)}
                                                    >
                                                        {entry.title}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        Ссылка ведёт на опубликованную запись с
                                        учётом языка посетителя.
                                    </p>
                                </div>
                            )}

                            {linkType === 'external' && (
                                <div className="space-y-2">
                                    <Label htmlFor="external-url">Адрес</Label>
                                    <Input
                                        id="external-url"
                                        type="url"
                                        placeholder="https://example.tj"
                                        value={externalUrl}
                                        onChange={(e) =>
                                            setExternalUrl(e.target.value)
                                        }
                                    />
                                    {errors.url && (
                                        <p className="text-xs text-destructive">
                                            {errors.url}
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label>Открытие</Label>
                                <Select
                                    value={data.target}
                                    onValueChange={(value) =>
                                        setData('target', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_self">
                                            В этой вкладке
                                        </SelectItem>
                                        <SelectItem value="_blank">
                                            В новой вкладке
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Отмена
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

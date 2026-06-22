import { useForm } from '@inertiajs/react';
import { FormEvent, useEffect } from 'react';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useTranslations } from '@/hooks/use-translations';
import { store, update } from '@/routes/admin/menus/items';

export default function MenuItemModal({
    isOpen,
    onClose,
    menuId,
    item,
    parentId,
    locales,
    defaultLocale,
}: {
    isOpen: boolean;
    onClose: () => void;
    menuId: number;
    item: any;
    parentId: number | null;
    locales: any[];
    defaultLocale: string;
}) {
    const { t } = useTranslations();
    const isEditing = !!item;

    const initialTranslations = locales.reduce((acc, locale) => {
        acc[locale.code] = { title: item?.translations?.[locale.code]?.title || '' };
        return acc;
    }, {} as Record<string, { title: string }>);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        parent_id: parentId || item?.parent_id || null,
        url: item?.url || '',
        route: item?.route || '',
        target: item?.target || '_self',
        translations: initialTranslations,
    });

    useEffect(() => {
        if (isOpen) {
            clearErrors();
        }
    }, [isOpen]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (isEditing) {
            put(update({ menu: menuId, item: item.id }).url, {
                onSuccess: () => {
                    reset();
                    onClose();
                },
            });
        } else {
            post(store({ menu: menuId }).url, {
                onSuccess: () => {
                    reset();
                    onClose();
                },
            });
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-[500px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEditing ? t('actions.edit') : t('actions.create')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('modules.menus.item_description')}
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
                                <TabsContent key={locale.code} value={locale.code} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor={`title-${locale.code}`}>
                                            {t('fields.title')} ({locale.code.toUpperCase()}) <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id={`title-${locale.code}`}
                                            value={data.translations[locale.code].title}
                                            onChange={(e) =>
                                                setData('translations', {
                                                    ...data.translations,
                                                    [locale.code]: {
                                                        ...data.translations[locale.code],
                                                        title: e.target.value,
                                                    },
                                                })
                                            }
                                        />
                                        {errors[`translations.${locale.code}.title`] && (
                                            <p className="text-xs text-destructive">
                                                {errors[`translations.${locale.code}.title`]}
                                            </p>
                                        )}
                                    </div>
                                </TabsContent>
                            ))}
                        </Tabs>

                        <div className="space-y-2">
                            <Label htmlFor="url">{t('fields.url')}</Label>
                            <Input
                                id="url"
                                type="url"
                                placeholder="https://..."
                                value={data.url}
                                onChange={(e) => setData('url', e.target.value)}
                            />
                            {errors.url && <p className="text-xs text-destructive">{errors.url}</p>}
                            <p className="text-xs text-muted-foreground">{t('modules.menus.url_help')}</p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            {t('actions.cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {t('actions.save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

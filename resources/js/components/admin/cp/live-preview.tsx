import { Eye } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';

type LocaleOption = { code: string; native_name: string };

/**
 * Opens a signed admin preview URL in an iframe (draft / moderation content).
 */
export function CpLivePreview({
    previewUrls,
    locales,
    activeLocale,
    open: controlledOpen,
    onOpenChange,
}: {
    previewUrls: Record<string, string>;
    locales: LocaleOption[];
    activeLocale: string;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}) {
    const [internalOpen, setInternalOpen] = useState(false);
    const open = controlledOpen ?? internalOpen;
    const setOpen = onOpenChange ?? setInternalOpen;
    const [locale, setLocale] = useState(activeLocale);

    const availableLocales = locales.filter((item) => previewUrls[item.code]);
    const src =
        previewUrls[locale] ?? previewUrls[availableLocales[0]?.code ?? ''];

    if (!src || availableLocales.length === 0) {
        return null;
    }

    const openPreview = () => {
        setLocale(activeLocale);
        setOpen(true);
    };

    return (
        <>
            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={openPreview}
            >
                <Eye className="size-4" />
                Предпросмотр
            </Button>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent
                    side="right"
                    className="flex w-full flex-col gap-0 p-0 sm:max-w-4xl"
                >
                    <SheetHeader className="border-b px-4 py-3">
                        <SheetTitle>Предпросмотр на сайте</SheetTitle>
                        <SheetDescription>
                            Черновик отображается так же, как на публичном
                            сайте. Ссылка действует ограниченное время.
                        </SheetDescription>
                        {availableLocales.length > 1 && (
                            <div className="flex flex-wrap gap-1 pt-2">
                                {availableLocales.map((item) => (
                                    <button
                                        key={item.code}
                                        type="button"
                                        onClick={() => setLocale(item.code)}
                                        className={cn(
                                            'rounded-md px-2 py-1 text-xs uppercase transition-colors',
                                            locale === item.code
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted text-muted-foreground hover:text-foreground',
                                        )}
                                    >
                                        {item.code}
                                    </button>
                                ))}
                            </div>
                        )}
                    </SheetHeader>

                    <iframe
                        title="Предпросмотр"
                        src={src}
                        className="min-h-0 flex-1 border-0 bg-background"
                    />
                </SheetContent>
            </Sheet>
        </>
    );
}

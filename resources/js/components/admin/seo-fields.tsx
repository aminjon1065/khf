import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type SeoMetaFieldsProps = {
    data: any;
    setData: (field: string, value: any) => void;
    errors: Partial<Record<string, string>>;
};

export function SeoMetaFields({ data, setData, errors }: SeoMetaFieldsProps) {
    return (
        <div className="space-y-4 rounded-md border p-4 bg-muted/50">
            <div>
                <h3 className="text-lg font-medium">SEO Настройки</h3>
                <p className="text-sm text-muted-foreground">
                    Оставьте поля пустыми, чтобы использовать автогенерацию из заголовка и контента.
                </p>
            </div>
            
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="meta_title">Meta Title</Label>
                    <Input
                        id="meta_title"
                        value={data.seo_meta?.meta_title ?? ''}
                        onChange={(e) =>
                            setData('seo_meta', { ...data.seo_meta, meta_title: e.target.value })
                        }
                        placeholder="Оптимально 50-60 символов"
                    />
                    {errors['seo_meta.meta_title'] && (
                        <p className="text-sm text-destructive">{errors['seo_meta.meta_title']}</p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="og_image">Open Graph Image URL</Label>
                    <Input
                        id="og_image"
                        value={data.seo_meta?.og_image ?? ''}
                        onChange={(e) =>
                            setData('seo_meta', { ...data.seo_meta, og_image: e.target.value })
                        }
                        placeholder="https://example.com/image.jpg"
                    />
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="meta_description">Meta Description</Label>
                <Textarea
                    id="meta_description"
                    value={data.seo_meta?.meta_description ?? ''}
                    onChange={(e) =>
                        setData('seo_meta', { ...data.seo_meta, meta_description: e.target.value })
                    }
                    placeholder="Оптимально 150-160 символов"
                    rows={3}
                />
            </div>
        </div>
    );
}

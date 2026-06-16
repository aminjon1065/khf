import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import { track } from '@/routes/tenders';

type TrackResult =
    | { found: false }
    | {
          found: true;
          reference: string;
          tender: string | null;
          status: string;
          created_at: string | null;
          updated_at: string | null;
      };

type PageProps = {
    reference: string;
    result: TrackResult | null;
};

export default function TenderTrack({ reference, result }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();
    const [value, setValue] = useState(reference ?? '');

    return (
        <>
            <Head title={t('tenders.track.title')} />

            <div className="mx-auto max-w-xl">
                <h1 className="text-3xl font-semibold">
                    {t('tenders.track.title')}
                </h1>
                <p className="mt-1 text-muted-foreground">
                    {t('tenders.track.hint')}
                </p>

                <form
                    className="mt-6 flex gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(
                            track({ locale }).url,
                            { reference: value },
                            { preserveState: true },
                        );
                    }}
                >
                    <Label htmlFor="reference" className="sr-only">
                        {t('common.reference_number')}
                    </Label>
                    <Input
                        id="reference"
                        value={value}
                        onChange={(event) => setValue(event.target.value)}
                        placeholder={t('tenders.track.reference_placeholder')}
                    />
                    <Button type="submit">{t('common.check')}</Button>
                </form>

                {result && !result.found && (
                    <p className="mt-6 rounded-md border border-destructive/40 bg-destructive/5 p-4 text-sm">
                        {t('tenders.track.not_found')}
                    </p>
                )}

                {result && result.found && (
                    <div className="mt-6 space-y-2 rounded-lg border p-4">
                        <div className="flex items-center justify-between">
                            <span className="font-mono font-semibold">
                                {result.reference}
                            </span>
                            <Badge>{result.status}</Badge>
                        </div>
                        {result.tender && (
                            <p className="font-medium">
                                {t('tenders.track.tender_label', {
                                    tender: result.tender,
                                })}
                            </p>
                        )}
                        <p className="text-sm text-muted-foreground">
                            {t('tenders.track.submitted_label', {
                                created_at: result.created_at ?? '',
                            })}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {t('tenders.track.updated_label', {
                                updated_at: result.updated_at ?? '',
                            })}
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}

import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { track } from '@/routes/tourist-groups';

type TrackResult =
    | { found: false }
    | {
          found: true;
          reference: string;
          route: string;
          status: string;
          start_date: string | null;
          end_date: string | null;
      };

type PageProps = {
    reference: string;
    result: TrackResult | null;
};

export default function TouristGroupTrack({ reference, result }: PageProps) {
    const { locale } = usePage().props;
    const [value, setValue] = useState(reference ?? '');

    return (
        <>
            <Head title="Отслеживание заявки" />

            <div className="mx-auto max-w-xl">
                <h1 className="text-3xl font-semibold">Отслеживание заявки</h1>
                <p className="mt-1 text-muted-foreground">Введите регистрационный номер заявки тургруппы</p>

                <form
                    className="mt-6 flex gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(track({ locale }).url, { reference: value }, { preserveState: true });
                    }}
                >
                    <Label htmlFor="reference" className="sr-only">
                        Регистрационный номер
                    </Label>
                    <Input
                        id="reference"
                        value={value}
                        onChange={(event) => setValue(event.target.value)}
                        placeholder="TUR-2026-XXXXXX"
                    />
                    <Button type="submit">Проверить</Button>
                </form>

                {result && !result.found && (
                    <p className="mt-6 rounded-md border border-destructive/40 bg-destructive/5 p-4 text-sm">
                        Заявка с таким номером не найдена.
                    </p>
                )}

                {result && result.found && (
                    <div className="mt-6 space-y-2 rounded-lg border p-4">
                        <div className="flex items-center justify-between">
                            <span className="font-mono font-semibold">{result.reference}</span>
                            <Badge>{result.status}</Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">Маршрут: {result.route}</p>
                        <p className="text-sm text-muted-foreground">
                            {result.start_date} — {result.end_date}
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}

import { Head, Link } from '@inertiajs/react';
import { Globe, Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes/admin';
import { index as globalsIndex } from '@/routes/admin/globals';

type GlobalItem = {
    handle: string;
    label: string;
    icon: string;
    edit_url: string;
};

type PageProps = {
    globals: GlobalItem[];
};

export default function GlobalsIndex({ globals }: PageProps) {
    return (
        <>
            <Head title="Глобальные настройки" />

            <div className="space-y-6 p-4 sm:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Глобальные настройки
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Сайтовые параметры, общие для всех языковых версий
                        портала.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    {globals.map((item) => (
                        <Card key={item.handle}>
                            <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                                <div className="space-y-1">
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <Globe className="size-4 text-muted-foreground" />
                                        {item.label}
                                    </CardTitle>
                                    <CardDescription className="font-mono text-xs">
                                        {item.handle}
                                    </CardDescription>
                                </div>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={item.edit_url}>
                                        <Pencil className="size-3.5" />
                                        Изменить
                                    </Link>
                                </Button>
                            </CardHeader>
                            <CardContent className="text-sm text-muted-foreground">
                                Настройки хранятся в CMS и применяются на
                                публичной части сайта.
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

GlobalsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Глобальные настройки', href: globalsIndex() },
    ],
};

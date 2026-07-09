import { Head, Link } from '@inertiajs/react';
import { Eye, Layers, Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes/admin';
import { index as blueprintsIndex } from '@/routes/admin/blueprints';

type BlueprintItem = {
    handle: string;
    collection: string;
    name: string;
    title: string;
    field_count: number;
    section_count: number;
    show_url: string;
    edit_url: string;
};

type PageProps = {
    blueprints: BlueprintItem[];
};

export default function BlueprintsIndex({ blueprints }: PageProps) {
    return (
        <>
            <Head title="Blueprint-схемы" />

            <div className="space-y-6 p-4 sm:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Blueprint-схемы
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        YAML-схемы полей для страниц, записей и глобальных
                        настроек. Редактирование пока через файлы в репозитории.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {blueprints.map((item) => (
                        <Card key={item.handle}>
                            <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                                <div className="space-y-1">
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <Layers className="size-4 text-muted-foreground" />
                                        {item.title}
                                    </CardTitle>
                                    <CardDescription className="font-mono text-xs">
                                        {item.handle}
                                    </CardDescription>
                                </div>
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={item.show_url}>
                                            <Eye className="size-3.5" />
                                            Схема
                                        </Link>
                                    </Button>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={item.edit_url}>
                                            <Pencil className="size-3.5" />
                                            YAML
                                        </Link>
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="text-sm text-muted-foreground">
                                {item.section_count} секций · {item.field_count}{' '}
                                полей
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

BlueprintsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Blueprint-схемы', href: blueprintsIndex() },
    ],
};

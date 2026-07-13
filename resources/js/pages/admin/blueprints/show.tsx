import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes/admin';
import { index as blueprintsIndex } from '@/routes/admin/blueprints';
import type {
    BlueprintDefinition,
    BlueprintFieldDefinition,
    BlueprintSubFieldDefinition,
} from '@/types/cms';

type PageProps = {
    blueprint: BlueprintDefinition;
    source: {
        path: string;
        yaml: string;
    };
    edit_url: string;
};

function FieldBadges({ field }: { field: BlueprintFieldDefinition }) {
    return (
        <div className="flex flex-wrap gap-1">
            <Badge variant="secondary">{field.type}</Badge>
            {field.localizable ? (
                <Badge variant="outline">локализуемое</Badge>
            ) : null}
            {field.required ? (
                <Badge variant="outline">обязательное</Badge>
            ) : null}
            {field.collection ? (
                <Badge variant="outline">{field.collection}</Badge>
            ) : null}
            {field.max != null ? (
                <Badge variant="outline">max {field.max}</Badge>
            ) : null}
        </div>
    );
}

function SubFieldsTable({
    subFields,
}: {
    subFields: BlueprintSubFieldDefinition[];
}) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Поле</TableHead>
                    <TableHead>Тип</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {subFields.map((subField) => (
                    <TableRow key={subField.handle}>
                        <TableCell>
                            <div className="font-medium">
                                {subField.display}
                            </div>
                            <div className="font-mono text-xs text-muted-foreground">
                                {subField.handle}
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge variant="secondary">{subField.type}</Badge>
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

export default function BlueprintShow({
    blueprint,
    source,
    edit_url,
}: PageProps) {
    const sections = Object.values(blueprint.sections);

    return (
        <>
            <Head title={blueprint.title} />

            <div className="space-y-6 p-4 sm:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={blueprintsIndex()}>
                                <ArrowLeft className="size-4" />
                                Все схемы
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {blueprint.title}
                            </h1>
                            <p className="mt-1 font-mono text-sm text-muted-foreground">
                                {blueprint.handle}
                            </p>
                        </div>
                    </div>
                    <Button asChild>
                        <Link href={edit_url}>
                            <Pencil className="size-4" />
                            Редактировать YAML
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
                    <div className="space-y-4">
                        {sections.map((section) => (
                            <Card key={section.handle}>
                                <CardHeader>
                                    <CardTitle>{section.display}</CardTitle>
                                    <CardDescription className="font-mono text-xs">
                                        {section.handle}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {section.fields.map((field) => (
                                        <div
                                            key={field.handle}
                                            className="rounded-lg border p-4"
                                        >
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <div className="font-medium">
                                                        {field.display}
                                                    </div>
                                                    <div className="font-mono text-xs text-muted-foreground">
                                                        {field.handle}
                                                    </div>
                                                    {field.instructions ? (
                                                        <p className="mt-2 text-sm text-muted-foreground">
                                                            {field.instructions}
                                                        </p>
                                                    ) : null}
                                                </div>
                                                <FieldBadges field={field} />
                                            </div>

                                            {field.sub_fields &&
                                            field.sub_fields.length > 0 ? (
                                                <div className="mt-4">
                                                    <p className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                        Вложенные поля
                                                    </p>
                                                    <SubFieldsTable
                                                        subFields={
                                                            field.sub_fields
                                                        }
                                                    />
                                                </div>
                                            ) : null}
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <Card className="h-fit xl:sticky xl:top-6">
                        <CardHeader>
                            <CardTitle>Исходный YAML</CardTitle>
                            <CardDescription className="font-mono text-xs">
                                {source.path}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <pre className="max-h-[70vh] overflow-auto rounded-md bg-muted p-4 text-xs leading-relaxed">
                                <code>{source.yaml}</code>
                            </pre>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

BlueprintShow.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Blueprint-схемы', href: blueprintsIndex() },
    ],
};

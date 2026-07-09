import { BlueprintBuilder } from '@/components/admin/blueprints/blueprint-builder';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Eye } from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import {
    stripBuilderIds,
    validateBuilderSchema,
    withBuilderIds,
    type BuilderSchema,
} from '@/lib/blueprint-builder';
import { dashboard } from '@/routes/admin';
import { index as blueprintsIndex, update } from '@/routes/admin/blueprints';
import type { BlueprintDefinition } from '@/types/cms';

type PageProps = {
    blueprint: {
        handle: string;
        title: string;
        collection: string;
        name: string;
    };
    schema: BlueprintDefinition;
    source: {
        path: string;
        yaml: string;
    };
    show_url: string;
};

export default function BlueprintEdit({
    blueprint,
    schema,
    source,
    show_url,
}: PageProps) {
    const initialBuilderSchema = useMemo(
        () => withBuilderIds(schema),
        [schema],
    );
    const [builderSchema, setBuilderSchema] =
        useState<BuilderSchema>(initialBuilderSchema);
    const [builderError, setBuilderError] = useState<string | null>(null);
    const [activeTab, setActiveTab] = useState('builder');

    const yamlForm = useForm({
        yaml: source.yaml,
    });

    const builderForm = useForm({
        schema: stripBuilderIds(initialBuilderSchema, blueprint.handle),
    });

    const submitYaml = (event: FormEvent) => {
        event.preventDefault();
        yamlForm.put(
            update({
                collection: blueprint.collection,
                name: blueprint.name,
            }).url,
            { preserveScroll: true },
        );
    };

    const submitBuilder = (event: FormEvent) => {
        event.preventDefault();

        const validationError = validateBuilderSchema(builderSchema);

        if (validationError) {
            setBuilderError(validationError);

            return;
        }

        setBuilderError(null);

        builderForm.setData(
            'schema',
            stripBuilderIds(builderSchema, blueprint.handle),
        );
        builderForm.put(
            update({
                collection: blueprint.collection,
                name: blueprint.name,
            }).url,
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Редактирование: ${blueprint.title}`} />

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
                                Редактирование: {blueprint.title}
                            </h1>
                            <p className="mt-1 font-mono text-sm text-muted-foreground">
                                {source.path}
                            </p>
                        </div>
                    </div>

                    <Button variant="outline" asChild>
                        <Link href={show_url}>
                            <Eye className="size-4" />
                            Просмотр схемы
                        </Link>
                    </Button>
                </div>

                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList>
                        <TabsTrigger value="builder">Конструктор</TabsTrigger>
                        <TabsTrigger value="yaml">YAML</TabsTrigger>
                    </TabsList>

                    <TabsContent value="builder" className="mt-6 space-y-4">
                        <form onSubmit={submitBuilder} className="space-y-4">
                            <BlueprintBuilder
                                schema={builderSchema}
                                onChange={setBuilderSchema}
                            />

                            {builderError ? (
                                <p className="text-sm text-destructive">
                                    {builderError}
                                </p>
                            ) : null}
                            <InputError
                                message={
                                    (builderForm.errors as Record<string, string>)
                                        .schema
                                }
                            />

                            <div className="flex flex-wrap gap-3">
                                <Button
                                    type="submit"
                                    disabled={builderForm.processing}
                                >
                                    Сохранить схему
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={show_url}>Отмена</Link>
                                </Button>
                            </div>
                        </form>
                    </TabsContent>

                    <TabsContent value="yaml" className="mt-6 space-y-4">
                        <form onSubmit={submitYaml} className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>YAML</CardTitle>
                                    <CardDescription>
                                        Прямое редактирование файла. Перед
                                        сохранением схема проверяется парсером
                                        blueprint.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <Textarea
                                        value={yamlForm.data.yaml}
                                        onChange={(event) =>
                                            yamlForm.setData(
                                                'yaml',
                                                event.target.value,
                                            )
                                        }
                                        rows={28}
                                        className="font-mono text-xs leading-relaxed"
                                        spellCheck={false}
                                    />
                                    <InputError message={yamlForm.errors.yaml} />
                                </CardContent>
                            </Card>

                            <div className="flex flex-wrap gap-3">
                                <Button
                                    type="submit"
                                    disabled={yamlForm.processing}
                                >
                                    Сохранить YAML
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={show_url}>Отмена</Link>
                                </Button>
                            </div>
                        </form>
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}

BlueprintEdit.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Blueprint-схемы', href: blueprintsIndex() },
    ],
};

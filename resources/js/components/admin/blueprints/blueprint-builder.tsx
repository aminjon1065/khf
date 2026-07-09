import { BlueprintFieldRow } from '@/components/admin/blueprints/blueprint-field-row';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    BLUEPRINT_FIELD_TYPES,
    addSectionField,
    moveSectionField,
    nextFieldIndex,
    removeSectionField,
    updateSectionField,
    type BuilderSchema,
} from '@/lib/blueprint-builder';
import {
    DndContext,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
    type DragEndEvent,
} from '@dnd-kit/core';
import {
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { Plus } from 'lucide-react';

export function BlueprintBuilder({
    schema,
    onChange,
}: {
    schema: BuilderSchema;
    onChange: (schema: BuilderSchema) => void;
}) {
    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const sections = Object.values(schema.sections);

    const handleDragEnd = (sectionHandle: string) => (event: DragEndEvent) => {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        onChange(
            moveSectionField(
                schema,
                sectionHandle,
                String(active.id),
                String(over.id),
            ),
        );
    };

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Название схемы</CardTitle>
                    <CardDescription>
                        Отображается в списке blueprint и в формах редактора.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-2">
                        <Label htmlFor="blueprint-title">Title</Label>
                        <Input
                            id="blueprint-title"
                            value={schema.title}
                            onChange={(event) =>
                                onChange({
                                    ...schema,
                                    title: event.target.value,
                                })
                            }
                        />
                    </div>
                </CardContent>
            </Card>

            {sections.map((section) => (
                <Card key={section.handle}>
                    <CardHeader>
                        <CardTitle>{section.display}</CardTitle>
                        <CardDescription className="font-mono text-xs">
                            section: {section.handle}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor={`section-${section.handle}-display`}>
                                Заголовок секции
                            </Label>
                            <Input
                                id={`section-${section.handle}-display`}
                                value={section.display}
                                onChange={(event) =>
                                    onChange({
                                        ...schema,
                                        sections: {
                                            ...schema.sections,
                                            [section.handle]: {
                                                ...section,
                                                display: event.target.value,
                                            },
                                        },
                                    })
                                }
                            />
                        </div>

                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragEnd={handleDragEnd(section.handle)}
                        >
                            <SortableContext
                                items={section.fields.map((field) => field.id)}
                                strategy={verticalListSortingStrategy}
                            >
                                <div className="space-y-3">
                                    {section.fields.map((field) => (
                                        <BlueprintFieldRow
                                            key={field.id}
                                            field={field}
                                            onChange={(patch) =>
                                                onChange(
                                                    updateSectionField(
                                                        schema,
                                                        section.handle,
                                                        field.id,
                                                        patch,
                                                    ),
                                                )
                                            }
                                            onRemove={() =>
                                                onChange(
                                                    removeSectionField(
                                                        schema,
                                                        section.handle,
                                                        field.id,
                                                    ),
                                                )
                                            }
                                        />
                                    ))}
                                </div>
                            </SortableContext>
                        </DndContext>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="w-full"
                                >
                                    <Plus className="mr-2 size-4" />
                                    Добавить поле
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="max-h-72 w-56 overflow-y-auto">
                                {BLUEPRINT_FIELD_TYPES.map((item) => (
                                    <DropdownMenuItem
                                        key={item.type}
                                        onClick={() =>
                                            onChange(
                                                addSectionField(
                                                    schema,
                                                    section.handle,
                                                    item.type,
                                                ),
                                            )
                                        }
                                    >
                                        {item.label}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <p className="text-xs text-muted-foreground">
                            Следующий автогенерируемый handle: field_
                            {nextFieldIndex(schema, section.handle)}
                        </p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

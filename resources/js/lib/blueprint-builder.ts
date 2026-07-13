import type {
    BlueprintDefinition,
    BlueprintFieldDefinition,
    BlueprintSectionDefinition,
} from '@/types/cms';

export type BuilderField = BlueprintFieldDefinition & {
    id: string;
};

export type BuilderSection = Omit<BlueprintSectionDefinition, 'fields'> & {
    fields: BuilderField[];
};

export type BuilderSchema = {
    title: string;
    sections: Record<string, BuilderSection>;
};

export type BlueprintFieldTypeOption = {
    type: string;
    label: string;
    defaults?: Partial<BlueprintFieldDefinition>;
};

export const BLUEPRINT_FIELD_TYPES: BlueprintFieldTypeOption[] = [
    { type: 'text', label: 'Текст' },
    { type: 'slug', label: 'Slug (ЧПУ)' },
    { type: 'textarea', label: 'Многострочный текст', defaults: { rows: 4 } },
    { type: 'rich_text', label: 'Rich text' },
    { type: 'select', label: 'Выбор (select)' },
    {
        type: 'entries',
        label: 'Связь (entries)',
        defaults: { collection: 'categories' },
    },
    { type: 'date', label: 'Дата' },
    { type: 'assets', label: 'Медиа (assets)', defaults: { max: 1 } },
    { type: 'toggle', label: 'Переключатель' },
    { type: 'number', label: 'Число' },
    { type: 'blocks', label: 'Блоки' },
    { type: 'grid', label: 'Таблица (grid)' },
    { type: 'replicator', label: 'Повторитель (replicator)' },
    { type: 'status', label: 'Статус публикации' },
];

const FIELD_TYPE_LABELS = Object.fromEntries(
    BLUEPRINT_FIELD_TYPES.map((item) => [item.type, item.label]),
) as Record<string, string>;

export function fieldTypeLabel(type: string): string {
    return FIELD_TYPE_LABELS[type] ?? type;
}

function createFieldId(): string {
    return Math.random().toString(36).slice(2, 11);
}

export function createBuilderField(type: string, index = 1): BuilderField {
    const definition = BLUEPRINT_FIELD_TYPES.find((item) => item.type === type);
    const handle = `field_${index}`;

    return {
        id: createFieldId(),
        handle,
        type,
        display: fieldTypeLabel(type),
        localizable: false,
        required: false,
        instructions: null,
        collection: definition?.defaults?.collection ?? null,
        max: definition?.defaults?.max ?? null,
        min: 0,
        rows: definition?.defaults?.rows ?? 4,
        sub_fields: [],
    };
}

export function withBuilderIds(schema: BlueprintDefinition): BuilderSchema {
    return {
        title: schema.title,
        sections: Object.fromEntries(
            Object.entries(schema.sections).map(([key, section]) => [
                key,
                {
                    ...section,
                    fields: section.fields.map((field) => ({
                        ...field,
                        id: createFieldId(),
                    })),
                },
            ]),
        ),
    };
}

export function stripBuilderIds(
    schema: BuilderSchema,
    handle?: string,
): BlueprintDefinition & { handle?: string } {
    const payload = {
        title: schema.title,
        sections: Object.fromEntries(
            Object.entries(schema.sections).map(([key, section]) => [
                key,
                {
                    handle: section.handle,
                    display: section.display,
                    fields: section.fields.map((field) => {
                        const { id, ...withoutId } = field;
                        void id;

                        return withoutId;
                    }),
                },
            ]),
        ),
    };

    return (
        handle ? { ...payload, handle } : payload
    ) as BlueprintDefinition & {
        handle?: string;
    };
}

export function validateBuilderSchema(schema: BuilderSchema): string | null {
    if (!schema.title.trim()) {
        return 'Укажите название blueprint.';
    }

    const sections = Object.values(schema.sections);

    if (sections.length === 0) {
        return 'Добавьте хотя бы одну секцию.';
    }

    for (const section of sections) {
        if (section.fields.length === 0) {
            return `Секция «${section.display}» должна содержать хотя бы одно поле.`;
        }

        const handles = section.fields.map((field) => field.handle.trim());
        const unique = new Set(handles);

        if (handles.some((handle) => handle === '')) {
            return `Заполните handle для всех полей в секции «${section.display}».`;
        }

        if (unique.size !== handles.length) {
            return `Handle полей в секции «${section.display}» должны быть уникальными.`;
        }
    }

    return null;
}

export function moveSectionField(
    schema: BuilderSchema,
    sectionHandle: string,
    activeId: string,
    overId: string,
): BuilderSchema {
    const section = schema.sections[sectionHandle];

    if (!section) {
        return schema;
    }

    const oldIndex = section.fields.findIndex((field) => field.id === activeId);
    const newIndex = section.fields.findIndex((field) => field.id === overId);

    if (oldIndex === -1 || newIndex === -1 || oldIndex === newIndex) {
        return schema;
    }

    const fields = [...section.fields];
    const [moved] = fields.splice(oldIndex, 1);
    fields.splice(newIndex, 0, moved);

    return {
        ...schema,
        sections: {
            ...schema.sections,
            [sectionHandle]: {
                ...section,
                fields,
            },
        },
    };
}

export function updateSectionField(
    schema: BuilderSchema,
    sectionHandle: string,
    fieldId: string,
    patch: Partial<BuilderField>,
): BuilderSchema {
    const section = schema.sections[sectionHandle];

    if (!section) {
        return schema;
    }

    return {
        ...schema,
        sections: {
            ...schema.sections,
            [sectionHandle]: {
                ...section,
                fields: section.fields.map((field) =>
                    field.id === fieldId ? { ...field, ...patch } : field,
                ),
            },
        },
    };
}

export function removeSectionField(
    schema: BuilderSchema,
    sectionHandle: string,
    fieldId: string,
): BuilderSchema {
    const section = schema.sections[sectionHandle];

    if (!section) {
        return schema;
    }

    return {
        ...schema,
        sections: {
            ...schema.sections,
            [sectionHandle]: {
                ...section,
                fields: section.fields.filter((field) => field.id !== fieldId),
            },
        },
    };
}

export function addSectionField(
    schema: BuilderSchema,
    sectionHandle: string,
    type: string,
): BuilderSchema {
    const section = schema.sections[sectionHandle];

    if (!section) {
        return schema;
    }

    const nextIndex = section.fields.length + 1;

    return {
        ...schema,
        sections: {
            ...schema.sections,
            [sectionHandle]: {
                ...section,
                fields: [
                    ...section.fields,
                    createBuilderField(type, nextIndex),
                ],
            },
        },
    };
}

export function nextFieldIndex(
    schema: BuilderSchema,
    sectionHandle: string,
): number {
    const section = schema.sections[sectionHandle];

    return (section?.fields.length ?? 0) + 1;
}

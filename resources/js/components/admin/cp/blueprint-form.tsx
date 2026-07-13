import { CpBlueprintField } from '@/components/admin/cp/blueprint-field';
import { CpPanel } from '@/components/admin/cp/publish-form';
import InputError from '@/components/input-error';
import type {
    BlueprintDefinition,
    BlueprintFieldDefinition,
    BlueprintFieldOptions,
    BlueprintFormMeta,
    BlockSetDefinition,
} from '@/types/cms';

function sectionFields(
    blueprint: BlueprintDefinition,
    section: string,
): BlueprintFieldDefinition[] {
    return blueprint.sections[section]?.fields ?? [];
}

function readFieldValue(
    field: BlueprintFieldDefinition,
    data: Record<string, unknown>,
    activeLocale: string,
): unknown {
    if (field.localizable) {
        const translations = data.translations as Record<
            string,
            Record<string, unknown>
        >;

        return translations?.[activeLocale]?.[field.handle] ?? '';
    }

    return data[field.handle] ?? '';
}

export function CpBlueprintForm({
    blueprint,
    section,
    data,
    errors,
    activeLocale,
    fieldOptions,
    meta,
    onRootChange,
    onTranslationChange,
    onAssetChange,
    titleAsHeader = false,
    titleFieldHandle = 'title',
    wrapInPanel = true,
    excludeHandles = [],
    blockset,
}: {
    blueprint: BlueprintDefinition;
    section: string;
    data: Record<string, unknown>;
    errors: Record<string, string | undefined>;
    activeLocale: string;
    fieldOptions: BlueprintFieldOptions;
    meta: BlueprintFormMeta;
    onRootChange: (handle: string, value: unknown) => void;
    onTranslationChange: (
        locale: string,
        handle: string,
        value: unknown,
    ) => void;
    onAssetChange: (patch: Record<string, unknown>) => void;
    titleAsHeader?: boolean;
    titleFieldHandle?: string;
    wrapInPanel?: boolean;
    excludeHandles?: string[];
    blockset?: BlockSetDefinition;
}) {
    const fields = sectionFields(blueprint, section).filter(
        (field) => !excludeHandles.includes(field.handle),
    );

    const titleField = titleAsHeader
        ? fields.find((field) => field.handle === titleFieldHandle)
        : null;
    const bodyFields = titleField
        ? fields.filter((field) => field.handle !== titleFieldHandle)
        : fields;

    const renderField = (field: BlueprintFieldDefinition) => (
        <CpBlueprintField
            key={`${field.handle}-${activeLocale}`}
            field={field}
            value={readFieldValue(field, data, activeLocale) as never}
            onChange={(value) => {
                if (field.localizable) {
                    onTranslationChange(activeLocale, field.handle, value);
                } else {
                    onRootChange(field.handle, value);
                }
            }}
            errors={errors}
            activeLocale={activeLocale}
            fieldOptions={fieldOptions}
            meta={meta}
            editorKey={activeLocale}
            data={data}
            onRootChange={onRootChange}
            onAssetChange={onAssetChange}
            blockset={blockset}
        />
    );

    const content = (
        <>
            {titleField && (
                <div>
                    <input
                        aria-label={titleField.display}
                        value={String(
                            readFieldValue(titleField, data, activeLocale) ??
                                '',
                        )}
                        onChange={(event) =>
                            onTranslationChange(
                                activeLocale,
                                titleFieldHandle,
                                event.target.value,
                            )
                        }
                        placeholder={titleField.display}
                        className="w-full rounded-sm border-0 bg-transparent px-0 text-2xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError
                        message={
                            errors[
                                `translations.${activeLocale}.${titleFieldHandle}`
                            ]
                        }
                    />
                </div>
            )}

            {bodyFields.map((field) => {
                if (field.type === 'status') {
                    return renderField(field);
                }

                if (field.type === 'blocks') {
                    return (
                        <CpPanel key={field.handle} title={field.display}>
                            {renderField(field)}
                        </CpPanel>
                    );
                }

                return renderField(field);
            })}
        </>
    );

    if (!wrapInPanel) {
        return <div className="space-y-4">{content}</div>;
    }

    const sectionMeta = blueprint.sections[section];

    return (
        <CpPanel title={sectionMeta?.display}>
            <div className="space-y-4">{content}</div>
        </CpPanel>
    );
}

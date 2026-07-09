export type BlueprintSubFieldDefinition = {
    handle: string;
    type: string;
    display: string;
    required?: boolean;
    rows?: number;
};

export type BlueprintFieldDefinition = {
    handle: string;
    type: string;
    display: string;
    localizable: boolean;
    required: boolean;
    instructions: string | null;
    collection: string | null;
    max: number | null;
    min?: number;
    rows: number;
    sub_fields?: BlueprintSubFieldDefinition[];
};

export type BlueprintSectionDefinition = {
    handle: string;
    display: string;
    fields: BlueprintFieldDefinition[];
};

export type BlueprintDefinition = {
    handle: string;
    title: string;
    sections: Record<string, BlueprintSectionDefinition>;
};

export type BlockTypeDefinition = {
    type: string;
    label: string;
    defaults: Record<string, unknown>;
};

export type BlockSetDefinition = {
    handle: string;
    blocks: BlockTypeDefinition[];
};

export type SelectOption = { value: string; label: string };
export type RelationOption = { id: number; name: string };

export type BlueprintFieldOptions = Record<
    string,
    SelectOption[] | RelationOption[]
>;

export type BlueprintFormMeta = {
    statuses: SelectOption[];
    statusTransitions: SelectOption[];
    showSchedule?: boolean;
    coverUrl?: string | null;
};

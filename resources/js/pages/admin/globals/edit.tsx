import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { CpBlueprintForm } from '@/components/admin/cp/blueprint-form';
import { GlobalsHelp } from '@/components/admin/cp/content-help-topics';
import { CpPublishForm } from '@/components/admin/cp/publish-form';
import { dashboard } from '@/routes/admin';
import { index as globalsIndex, update } from '@/routes/admin/globals';
import type { BlueprintDefinition, BlueprintFieldOptions } from '@/types/cms';

type GlobalMeta = {
    handle: string;
    label: string;
};

type PageProps = {
    global: GlobalMeta;
    fields: Record<string, unknown>;
    blueprint: BlueprintDefinition;
};

const emptyFieldOptions: BlueprintFieldOptions = {};

const emptyMeta = {
    statuses: [],
    statusTransitions: [],
};

function mapFieldErrors(
    errors: Record<string, string>,
): Record<string, string | undefined> {
    return Object.fromEntries(
        Object.entries(errors).map(([key, value]) => [
            key.startsWith('fields.') ? key.slice('fields.'.length) : key,
            value,
        ]),
    );
}

export default function GlobalEdit({ global, fields, blueprint }: PageProps) {
    const form = useForm<Record<string, any>>({
        fields: { ...fields } as Record<string, any>,
    });

    const errors = mapFieldErrors(form.errors as Record<string, string>);

    const setField = (handle: string, value: unknown) => {
        form.setData('fields', {
            ...form.data.fields,
            [handle]: value,
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update(global.handle).url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={global.label} />

            <CpPublishForm
                title={global.label}
                backHref={globalsIndex().url}
                onSubmit={submit}
                processing={form.processing}
            >
                <GlobalsHelp />

                <CpBlueprintForm
                    blueprint={blueprint}
                    section="main"
                    data={form.data.fields}
                    errors={errors}
                    activeLocale="tj"
                    fieldOptions={emptyFieldOptions}
                    meta={emptyMeta}
                    onRootChange={setField}
                    onTranslationChange={() => {}}
                    onAssetChange={() => {}}
                />
            </CpPublishForm>
        </>
    );
}

GlobalEdit.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Глобальные настройки', href: globalsIndex() },
        { title: 'Редактирование', href: '#' },
    ],
};

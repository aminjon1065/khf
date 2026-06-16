import { Head } from '@inertiajs/react';
import { Mail, MapPin, Phone, Users } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

type Node = {
    id: number;
    name: string | null;
    head: string | null;
    functions: string | null;
    address: string | null;
    email: string | null;
    phone: string | null;
    staff_count: number | null;
    children: Node[];
};

function SubdivisionNode({ node }: { node: Node }) {
    const { t } = useTranslations();

    return (
        <li>
            <div className="rounded-lg border p-4">
                <h2 className="font-semibold">{node.name}</h2>
                {node.head && (
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        <span className="font-medium">
                            {t('structure.head')}:
                        </span>{' '}
                        {node.head}
                    </p>
                )}
                {node.functions && (
                    <div
                        className="rte-content mt-2 text-sm leading-relaxed text-muted-foreground"
                        dangerouslySetInnerHTML={{ __html: node.functions }}
                    />
                )}
                <div className="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted-foreground">
                    {node.address && (
                        <span className="inline-flex items-center gap-1.5">
                            <MapPin className="size-4" />
                            {node.address}
                        </span>
                    )}
                    {node.phone && (
                        <a
                            href={`tel:${node.phone}`}
                            className="inline-flex items-center gap-1.5 hover:text-primary"
                        >
                            <Phone className="size-4" />
                            {node.phone}
                        </a>
                    )}
                    {node.email && (
                        <a
                            href={`mailto:${node.email}`}
                            className="inline-flex items-center gap-1.5 hover:text-primary"
                        >
                            <Mail className="size-4" />
                            {node.email}
                        </a>
                    )}
                    {node.staff_count !== null && (
                        <span className="inline-flex items-center gap-1.5">
                            <Users className="size-4" />
                            {t('structure.staff', { count: node.staff_count })}
                        </span>
                    )}
                </div>
            </div>

            {node.children.length > 0 && (
                <ul className="mt-3 space-y-3 border-l-2 border-border pl-4 sm:pl-6">
                    {node.children.map((child) => (
                        <SubdivisionNode key={child.id} node={child} />
                    ))}
                </ul>
            )}
        </li>
    );
}

export default function StructureIndex({ tree }: { tree: Node[] }) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('structure.title')} />

            <h1 className="text-3xl font-semibold">{t('structure.title')}</h1>
            <p className="mt-1 text-muted-foreground">
                {t('structure.subtitle')}
            </p>

            {tree.length === 0 ? (
                <p className="mt-8 text-muted-foreground">
                    {t('structure.empty')}
                </p>
            ) : (
                <ul className="mt-6 space-y-3">
                    {tree.map((node) => (
                        <SubdivisionNode key={node.id} node={node} />
                    ))}
                </ul>
            )}
        </>
    );
}

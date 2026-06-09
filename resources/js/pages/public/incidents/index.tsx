import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type {Paginator} from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type IncidentItem = {
    title: string | null;
    description: string | null;
    type_label: string;
    hazard_label: string;
    hazard_color: string;
    status: string;
    status_label: string;
    region: string | null;
    occurred_at: string | null;
};

type PageProps = {
    incidents: Paginator<IncidentItem> & { prev_page_url: string | null; next_page_url: string | null };
};

export default function IncidentsArchive({ incidents }: PageProps) {
    return (
        <>
            <Head title="Оперативная обстановка" />

            <h1 className="mb-2 text-3xl font-semibold">Оперативная обстановка</h1>
            <p className="mb-6 text-muted-foreground">События и чрезвычайные ситуации</p>

            {incidents.data.length === 0 ? (
                <p className="text-muted-foreground">Зарегистрированных событий нет.</p>
            ) : (
                <div className="space-y-4">
                    {incidents.data.map((incident, idx) => (
                        <div key={idx} className="rounded-lg border p-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <span
                                    className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white"
                                    style={{ backgroundColor: incident.hazard_color }}
                                >
                                    {incident.hazard_label}
                                </span>
                                <Badge variant="secondary">{incident.type_label}</Badge>
                                <Badge variant="outline">{incident.status_label}</Badge>
                                {incident.region && (
                                    <span className="text-sm text-muted-foreground">{incident.region}</span>
                                )}
                                {incident.occurred_at && (
                                    <span className="ml-auto text-sm text-muted-foreground">{incident.occurred_at}</span>
                                )}
                            </div>
                            <h2 className="mt-2 text-lg font-semibold">{incident.title}</h2>
                            {incident.description && (
                                <p className="mt-1 text-sm text-muted-foreground">{incident.description}</p>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {(incidents.prev_page_url || incidents.next_page_url) && (
                <div className="mt-8 flex items-center justify-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!incidents.prev_page_url}
                        onClick={() => incidents.prev_page_url && router.get(incidents.prev_page_url)}
                    >
                        <ChevronLeft className="size-4" />
                        Назад
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!incidents.next_page_url}
                        onClick={() => incidents.next_page_url && router.get(incidents.next_page_url)}
                    >
                        Вперёд
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            )}
        </>
    );
}

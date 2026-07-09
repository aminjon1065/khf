import { Head, Link } from '@inertiajs/react';
import {
    BarChart3,
    Bell,
    BookOpen,
    Briefcase,
    CircleHelp,
    FileText,
    Gavel,
    Image,
    Landmark,
    LayoutGrid,
    Network,
    Newspaper,
    Siren,
    Users,
    Vote,
} from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes/admin';
import { hub as contentHub } from '@/routes/admin/content';
import { ContentHubHelp } from '@/components/admin/cp/content-help-topics';

type ContentTypeCard = {
    handle: string;
    label: string;
    icon: string;
    count: number;
    url: string;
};

type PageProps = {
    types: ContentTypeCard[];
};

const iconMap: Record<string, typeof FileText> = {
    'file-text': FileText,
    newspaper: Newspaper,
    'file-stack': FileText,
    'book-open': BookOpen,
    images: Image,
    'circle-help': CircleHelp,
    vote: Vote,
    landmark: Landmark,
    'chart-bar': BarChart3,
    users: Users,
    network: Network,
    briefcase: Briefcase,
    gavel: Gavel,
    siren: Siren,
    bell: Bell,
};

export default function ContentHub({ types }: PageProps) {
    return (
        <>
            <Head title="Коллекции контента" />

            <div className="space-y-6 p-4 sm:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Коллекции контента
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Единый браузер материалов CMS — как коллекции в
                        Statamic.
                    </p>
                </div>

                <ContentHubHelp />

                {types.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Нет доступных коллекций</CardTitle>
                            <CardDescription>
                                У вашей учётной записи нет прав на управление
                                контентом.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {types.map((type) => {
                            const Icon = iconMap[type.icon] ?? LayoutGrid;

                            return (
                                <Link key={type.handle} href={type.url}>
                                    <Card className="h-full transition-colors hover:border-primary/40 hover:bg-muted/30">
                                        <CardHeader className="flex flex-row items-start justify-between gap-3 space-y-0">
                                            <div className="space-y-1">
                                                <CardTitle className="flex items-center gap-2 text-lg">
                                                    <Icon className="size-4 text-muted-foreground" />
                                                    {type.label}
                                                </CardTitle>
                                                <CardDescription className="font-mono text-xs">
                                                    {type.handle}
                                                </CardDescription>
                                            </div>
                                            <span className="rounded-full bg-muted px-2.5 py-0.5 text-sm font-medium tabular-nums">
                                                {type.count}
                                            </span>
                                        </CardHeader>
                                        <CardContent className="text-sm text-muted-foreground">
                                            Открыть браузер записей
                                        </CardContent>
                                    </Card>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}

ContentHub.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Коллекции', href: contentHub() },
    ],
};

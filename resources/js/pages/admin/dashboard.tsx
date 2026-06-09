import { Head } from '@inertiajs/react';
import { Languages, ShieldCheck, Users } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard as adminDashboard } from '@/routes/admin';

type DashboardProps = {
    stats: {
        users: number;
        languages: number;
        roles: number;
    };
};

export default function AdminDashboard({ stats }: DashboardProps) {
    const cards = [
        { label: 'Пользователи', value: stats.users, icon: Users },
        { label: 'Языки', value: stats.languages, icon: Languages },
        { label: 'Роли', value: stats.roles, icon: ShieldCheck },
    ];

    return (
        <>
            <Head title="Панель управления" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Панель управления</h1>
                    <p className="text-sm text-muted-foreground">Обзор системы КЧС</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {cards.map((card) => (
                        <Card key={card.label}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    {card.label}
                                </CardTitle>
                                <card.icon className="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-semibold">{card.value}</div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Модули</CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground">
                        Управление контентом, событиями ЧС, оповещениями, обращениями и
                        пользователями появится здесь по мере подключения модулей.
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Панель управления', href: adminDashboard() }],
};

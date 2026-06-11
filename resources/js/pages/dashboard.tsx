import { Head } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle2,
    Clock,
    FileText,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';

const stats = [
    {
        name: 'Total Records',
        value: '24,501',
        icon: FileText,
        description: 'Updated just now',
    },
    {
        name: 'Active Users',
        value: '1,234',
        icon: Users,
        description: '+12% from last month',
    },
    {
        name: 'Pending Reviews',
        value: '142',
        icon: Clock,
        description: 'Requires attention',
    },
];

const recentActivity = [
    {
        id: '1',
        type: 'Permit Application',
        subject: 'LLC "StroyTech"',
        status: 'Pending',
        date: '2 hours ago',
    },
    {
        id: '2',
        type: 'System Audit',
        subject: 'Security Policy Update',
        status: 'Completed',
        date: '4 hours ago',
    },
    {
        id: '3',
        type: 'Incident Report',
        subject: 'Sector 7 Protocol',
        status: 'Critical',
        date: '5 hours ago',
    },
    {
        id: '4',
        type: 'User Registration',
        subject: 'Alisher O.',
        status: 'Active',
        date: '1 day ago',
    },
    {
        id: '5',
        type: 'Permit Application',
        subject: 'JSC "Global Resources"',
        status: 'Approved',
        date: '1 day ago',
    },
];

function StatusBadge({ status }: { status: string }) {
    switch (status) {
        case 'Completed':
        case 'Active':
        case 'Approved':
            return (
                <span className="inline-flex items-center gap-1.5 rounded-md border border-hazard-normal/20 bg-hazard-normal/10 px-2.5 py-0.5 text-xs font-semibold text-hazard-normal dark:bg-hazard-normal/20 dark:text-green-400">
                    <CheckCircle2 className="size-3.5" />
                    {status}
                </span>
            );
        case 'Pending':
            return (
                <span className="inline-flex items-center gap-1.5 rounded-md border border-hazard-elevated/20 bg-hazard-elevated/10 px-2.5 py-0.5 text-xs font-semibold text-hazard-elevated dark:bg-hazard-elevated/20 dark:text-yellow-400">
                    <Clock className="size-3.5" />
                    {status}
                </span>
            );
        case 'Critical':
            return (
                <span className="inline-flex items-center gap-1.5 rounded-md border border-hazard-critical/20 bg-hazard-critical/10 px-2.5 py-0.5 text-xs font-semibold text-hazard-critical dark:bg-hazard-critical/20 dark:text-red-400">
                    <AlertCircle className="size-3.5" />
                    {status}
                </span>
            );
        default:
            return (
                <span className="inline-flex items-center gap-1.5 rounded-md border border-border bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground">
                    {status}
                </span>
            );
    }
}

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold tracking-tight text-foreground">
                        System Dashboard
                    </h1>
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    {stats.map((stat, i) => (
                        <div
                            key={i}
                            className="flex flex-col gap-2 rounded-xl border border-border bg-card p-6 shadow-sm transition-shadow hover:shadow-md"
                        >
                            <div className="flex items-center gap-4">
                                <div className="flex size-12 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-primary/20">
                                    <stat.icon className="size-6" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">
                                        {stat.name}
                                    </p>
                                    <h2 className="text-3xl font-bold text-foreground">
                                        {stat.value}
                                    </h2>
                                </div>
                            </div>
                            <p className="mt-2 border-t border-border/50 pt-3 text-sm text-muted-foreground">
                                {stat.description}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="flex-1 overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                    <div className="border-b border-border bg-muted/30 px-6 py-4">
                        <h3 className="text-lg font-medium text-foreground">
                            Recent Activity
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            Overview of latest system events and submissions.
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-6 py-3 font-medium">
                                        Activity Type
                                    </th>
                                    <th className="px-6 py-3 font-medium">
                                        Subject
                                    </th>
                                    <th className="px-6 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-right font-medium">
                                        Time
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {recentActivity.map((activity) => (
                                    <tr
                                        key={activity.id}
                                        className="transition-colors hover:bg-muted/30"
                                    >
                                        <td className="px-6 py-4 font-medium whitespace-nowrap text-foreground">
                                            {activity.type}
                                        </td>
                                        <td className="px-6 py-4 text-muted-foreground">
                                            {activity.subject}
                                        </td>
                                        <td className="px-6 py-4">
                                            <StatusBadge
                                                status={activity.status}
                                            />
                                        </td>
                                        <td className="px-6 py-4 text-right whitespace-nowrap text-muted-foreground">
                                            {activity.date}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};

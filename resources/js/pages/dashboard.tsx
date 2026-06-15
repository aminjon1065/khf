import { Head } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle2,
    Clock,
    FileText,
    Users,
} from 'lucide-react';
import { motion } from 'framer-motion';
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
                <span className="inline-flex items-center gap-1.5 rounded-full border border-hazard-normal/20 bg-hazard-normal/5 px-2.5 py-1 text-[11px] font-medium tracking-wide text-hazard-normal dark:bg-hazard-normal/10 dark:text-green-400">
                    <span className="size-1.5 rounded-full bg-hazard-normal"></span>
                    {status}
                </span>
            );
        case 'Pending':
            return (
                <span className="inline-flex items-center gap-1.5 rounded-full border border-hazard-elevated/20 bg-hazard-elevated/5 px-2.5 py-1 text-[11px] font-medium tracking-wide text-hazard-elevated dark:bg-hazard-elevated/10 dark:text-yellow-400">
                    <span className="size-1.5 rounded-full bg-hazard-elevated"></span>
                    {status}
                </span>
            );
        case 'Critical':
            return (
                <span className="inline-flex items-center gap-1.5 rounded-full border border-hazard-critical/20 bg-hazard-critical/5 px-2.5 py-1 text-[11px] font-medium tracking-wide text-hazard-critical dark:bg-hazard-critical/10 dark:text-red-400">
                    <span className="size-1.5 rounded-full bg-hazard-critical"></span>
                    {status}
                </span>
            );
        default:
            return (
                <span className="inline-flex items-center gap-1.5 rounded-full border border-border bg-muted/50 px-2.5 py-1 text-[11px] font-medium tracking-wide text-muted-foreground">
                    <span className="size-1.5 rounded-full bg-muted-foreground/50"></span>
                    {status}
                </span>
            );
    }
}

const container = {
    hidden: { opacity: 0 },
    show: {
        opacity: 1,
        transition: {
            staggerChildren: 0.1,
        },
    },
};

const item = {
    hidden: { opacity: 0, y: 10 },
    show: {
        opacity: 1,
        y: 0,
        transition: { type: 'spring', stiffness: 300, damping: 24 },
    },
};

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <motion.div
                className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 p-6 lg:p-8"
                variants={container}
                initial="hidden"
                animate="show"
            >
                <motion.div
                    variants={item}
                    className="flex items-center justify-between"
                >
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">
                            System Overview
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Welcome back. Here's what's happening today.
                        </p>
                    </div>
                </motion.div>

                <motion.div
                    variants={item}
                    className="grid gap-6 md:grid-cols-3"
                >
                    {stats.map((stat, i) => (
                        <div
                            key={i}
                            className="group flex flex-col gap-2 rounded-2xl border border-border/50 bg-card p-6 shadow-sm transition-all hover:border-border hover:shadow-md"
                        >
                            <div className="flex items-center gap-4">
                                <div className="flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary transition-transform group-hover:scale-105 dark:bg-primary/20">
                                    <stat.icon className="size-5" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">
                                        {stat.name}
                                    </p>
                                    <h2 className="text-3xl font-bold tracking-tight text-foreground">
                                        {stat.value}
                                    </h2>
                                </div>
                            </div>
                            <p className="mt-3 text-xs text-muted-foreground">
                                {stat.description}
                            </p>
                        </div>
                    ))}
                </motion.div>

                <motion.div
                    variants={item}
                    className="flex-1 overflow-hidden rounded-2xl border border-border/50 bg-card shadow-sm"
                >
                    <div className="border-b border-border/50 px-6 py-5">
                        <h3 className="text-lg font-semibold text-foreground">
                            Recent Activity
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            Latest system events and submissions across the
                            platform.
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/20 text-xs tracking-wider text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-6 py-4 font-medium">
                                        Activity Type
                                    </th>
                                    <th className="px-6 py-4 font-medium">
                                        Subject
                                    </th>
                                    <th className="px-6 py-4 font-medium">
                                        Status
                                    </th>
                                    <th className="px-6 py-4 text-right font-medium">
                                        Time
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border/50">
                                {recentActivity.map((activity) => (
                                    <tr
                                        key={activity.id}
                                        className="group transition-colors hover:bg-muted/30"
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
                                        <td className="px-6 py-4 text-right whitespace-nowrap text-muted-foreground/80 group-hover:text-muted-foreground">
                                            {activity.date}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </motion.div>
            </motion.div>
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

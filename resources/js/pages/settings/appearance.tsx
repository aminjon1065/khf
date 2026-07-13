import { Head } from '@inertiajs/react';
import { motion } from 'framer-motion';
import AppearanceTabs from '@/components/appearance-tabs';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { edit as editAppearance } from '@/routes/appearance';

const container = {
    hidden: { opacity: 0 },
    show: { opacity: 1, transition: { staggerChildren: 0.1 } },
};

const item = {
    hidden: { opacity: 0, y: 10 },
    show: {
        opacity: 1,
        y: 0,
        transition: { type: 'spring' as const, stiffness: 300, damping: 24 },
    },
};

export default function Appearance() {
    return (
        <>
            <Head title="Appearance settings" />
            <h1 className="sr-only">Appearance settings</h1>

            <motion.div
                className="mx-auto flex w-full max-w-4xl flex-col gap-8 p-6 lg:p-8"
                variants={container}
                initial="hidden"
                animate="show"
            >
                <motion.div variants={item}>
                    <Card className="border-border/50 shadow-sm transition-all hover:shadow-md">
                        <CardHeader>
                            <CardTitle>Appearance Settings</CardTitle>
                            <CardDescription>
                                Customize the look and feel of your account
                                interface.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <AppearanceTabs />
                        </CardContent>
                    </Card>
                </motion.div>
            </motion.div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Appearance settings',
            href: editAppearance(),
        },
    ],
};

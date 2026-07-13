import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

const container = {
    hidden: { opacity: 0 },
    show: {
        opacity: 1,
        transition: { staggerChildren: 0.1 },
    },
};

const item = {
    hidden: { opacity: 0, y: 10 },
    show: {
        opacity: 1,
        y: 0,
        transition: { type: 'spring' as const, stiffness: 300, damping: 24 },
    },
};

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <motion.div
                className="mx-auto flex w-full max-w-4xl flex-col gap-8 p-6 lg:p-8"
                variants={container}
                initial="hidden"
                animate="show"
            >
                <motion.div variants={item}>
                    <Card className="border-border/50 shadow-sm transition-all hover:shadow-md">
                        <CardHeader>
                            <CardTitle>Profile Information</CardTitle>
                            <CardDescription>
                                Update your account's profile information and
                                email address.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...ProfileController.update.form()}
                                options={{
                                    preserveScroll: true,
                                }}
                                className="space-y-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">Name</Label>

                                            <Input
                                                id="name"
                                                className="mt-1 block w-full bg-muted/30 focus-visible:bg-transparent"
                                                defaultValue={auth.user.name}
                                                name="name"
                                                required
                                                autoComplete="name"
                                                placeholder="Your full name"
                                            />

                                            <InputError
                                                className="mt-2"
                                                message={errors.name}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="email">
                                                Email Address
                                            </Label>

                                            <Input
                                                id="email"
                                                type="email"
                                                className="mt-1 block w-full bg-muted/30 focus-visible:bg-transparent"
                                                defaultValue={auth.user.email}
                                                name="email"
                                                required
                                                autoComplete="username"
                                                placeholder="Your email address"
                                            />

                                            <InputError
                                                className="mt-2"
                                                message={errors.email}
                                            />
                                        </div>

                                        {mustVerifyEmail &&
                                            auth.user.email_verified_at ===
                                                null && (
                                                <div>
                                                    <p className="-mt-4 text-sm text-muted-foreground">
                                                        Your email address is
                                                        unverified.{' '}
                                                        <Link
                                                            href={send()}
                                                            as="button"
                                                            className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                        >
                                                            Click here to
                                                            re-send the
                                                            verification email.
                                                        </Link>
                                                    </p>

                                                    {status ===
                                                        'verification-link-sent' && (
                                                        <div className="mt-2 text-sm font-medium text-green-600">
                                                            A new verification
                                                            link has been sent
                                                            to your email
                                                            address.
                                                        </div>
                                                    )}
                                                </div>
                                            )}

                                        <div className="flex items-center gap-4 pt-4">
                                            <Button
                                                disabled={processing}
                                                data-test="update-profile-button"
                                            >
                                                Save Changes
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </motion.div>

                <motion.div variants={item}>
                    <div className="rounded-xl border border-destructive/20 bg-destructive/5 p-6 dark:border-destructive/30 dark:bg-destructive/10">
                        <DeleteUser />
                    </div>
                </motion.div>
            </motion.div>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};

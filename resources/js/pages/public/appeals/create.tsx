import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/hooks/use-translations';
import { useMatomoGoal } from '@/hooks/use-matomo-goal';
import { create, store, track } from '@/routes/appeals';

type Option = { value: string; label: string };

type PageProps = {
    categories: Option[];
    submittedReference: string | null;
};

export default function AppealCreate({
    categories,
    submittedReference,
}: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    const form = useForm<{
        category: string;
        name: string;
        email: string;
        phone: string;
        subject: string;
        message: string;
        website: string;
        attachments: File[];
    }>({
        category: categories[0]?.value ?? '',
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
        website: '',
        attachments: [],
    });

    const errors = form.errors as Record<string, string>;

    useMatomoGoal('appeal', submittedReference !== null);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store({ locale }).url, { preserveScroll: true });
    };

    if (submittedReference) {
        return (
            <>
                <Head title={t('appeals.success.title')} />
                <div className="mx-auto max-w-xl rounded-lg border p-8 text-center">
                    <CheckCircle2 className="mx-auto size-12 text-green-600" />
                    <h1 className="mt-4 text-2xl font-semibold">
                        {t('appeals.success.title')}
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        {t('appeals.success.reference_hint')}
                    </p>
                    <p className="mt-2 font-mono text-xl font-semibold">
                        {submittedReference}
                    </p>
                    <div className="mt-6 flex justify-center gap-3">
                        <Button variant="outline" asChild>
                            <Link href={track({ locale }).url}>
                                {t('common.track_status')}
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={create({ locale }).url}>
                                {t('appeals.success.new_appeal')}
                            </Link>
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={t('appeals.title')} />

            <div className="mx-auto max-w-2xl">
                <h1 className="text-3xl font-semibold">{t('appeals.title')}</h1>
                <p className="mt-1 text-muted-foreground">
                    {t('appeals.subtitle')}
                </p>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="category">
                            {t('appeals.form.category')}
                        </Label>
                        <Select
                            value={form.data.category}
                            onValueChange={(value) =>
                                form.setData('category', value)
                            }
                        >
                            <SelectTrigger
                                id="category"
                                aria-invalid={!!errors.category}
                                aria-describedby={
                                    errors.category
                                        ? 'category-error'
                                        : undefined
                                }
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((category) => (
                                    <SelectItem
                                        key={category.value}
                                        value={category.value}
                                    >
                                        {category.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError
                            id="category-error"
                            message={errors.category}
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="name">
                                {t('appeals.form.name')}
                            </Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                aria-invalid={!!errors.name}
                                aria-describedby={
                                    errors.name ? 'name-error' : undefined
                                }
                            />
                            <InputError id="name-error" message={errors.name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="email">{t('common.email')}</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                                aria-invalid={!!errors.email}
                                aria-describedby={
                                    errors.email ? 'email-error' : undefined
                                }
                            />
                            <InputError
                                id="email-error"
                                message={errors.email}
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phone">
                            {t('appeals.form.phone_optional')}
                        </Label>
                        <Input
                            id="phone"
                            value={form.data.phone}
                            onChange={(e) =>
                                form.setData('phone', e.target.value)
                            }
                            aria-invalid={!!errors.phone}
                            aria-describedby={
                                errors.phone ? 'phone-error' : undefined
                            }
                        />
                        <InputError id="phone-error" message={errors.phone} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="subject">
                            {t('appeals.form.subject')}
                        </Label>
                        <Input
                            id="subject"
                            value={form.data.subject}
                            onChange={(e) =>
                                form.setData('subject', e.target.value)
                            }
                            aria-invalid={!!errors.subject}
                            aria-describedby={
                                errors.subject ? 'subject-error' : undefined
                            }
                        />
                        <InputError
                            id="subject-error"
                            message={errors.subject}
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="message">
                            {t('appeals.form.message')}
                        </Label>
                        <Textarea
                            id="message"
                            rows={6}
                            value={form.data.message}
                            onChange={(e) =>
                                form.setData('message', e.target.value)
                            }
                            aria-invalid={!!errors.message}
                            aria-describedby={
                                errors.message ? 'message-error' : undefined
                            }
                        />
                        <InputError
                            id="message-error"
                            message={errors.message}
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="attachments">
                            {t('common.attachments') || 'Прикрепленные файлы'} <span className="text-muted-foreground font-normal">(Макс 5 файлов, до 5МБ каждый)</span>
                        </Label>
                        <Input
                            id="attachments"
                            type="file"
                            multiple
                            onChange={(e) => {
                                if (e.target.files) {
                                    form.setData('attachments', Array.from(e.target.files));
                                }
                            }}
                            aria-invalid={!!errors.attachments}
                            aria-describedby={
                                errors.attachments ? 'attachments-error' : undefined
                            }
                        />
                        <InputError
                            id="attachments-error"
                            message={errors.attachments}
                        />
                        {/* Display errors for individual files if any */}
                        {Object.keys(errors).filter(k => k.startsWith('attachments.')).map(key => (
                            <InputError
                                key={key}
                                message={errors[key]}
                            />
                        ))}
                    </div>

                    {/* Honeypot — hidden from users, traps bots (ТЗ §12.4). */}
                    <input
                        type="text"
                        tabIndex={-1}
                        autoComplete="off"
                        aria-hidden="true"
                        className="hidden"
                        value={form.data.website}
                        onChange={(e) =>
                            form.setData('website', e.target.value)
                        }
                    />

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {t('appeals.form.submit')}
                        </Button>
                        <Link
                            href={track({ locale }).url}
                            className="text-sm text-primary hover:underline"
                        >
                            {t('appeals.track_existing')}
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
}

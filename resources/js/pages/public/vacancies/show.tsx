import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    Briefcase,
    CalendarClock,
    CheckCircle2,
    MapPin,
    Wallet,
} from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Icon } from '@/components/ui/icon';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/hooks/use-translations';
import { apply, index as vacanciesIndex, track } from '@/routes/vacancies';
import { MissingTranslationAlert } from '@/components/Public/missing-translation-alert';

type Vacancy = {
    id: number;
    title: string;
    department: string | null;
    location: string | null;
    salary: string | null;
    summary: string | null;
    description: string | null;
    requirements: string | null;
    responsibilities: string | null;
    employment_type_label: string;
    positions_count: number;
    published_at: string | null;
    updated_at: string | null;
    deadline_at: string | null;
    is_open: boolean;
    locale?: string;
};

type PageProps = {
    vacancy: Vacancy;
    submittedReference: string | null;
};

export default function VacancyShow({
    vacancy,
    submittedReference,
}: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    const form = useForm({
        full_name: '',
        email: '',
        phone: '',
        cover_letter: '',
        resume: null as File | null,
        website: '',
    });

    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(apply({ locale, vacancy: vacancy.id }).url, {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const richSections = [
        { key: 'description', value: vacancy.description },
        { key: 'requirements', value: vacancy.requirements },
        { key: 'responsibilities', value: vacancy.responsibilities },
    ] as const;

    return (
        <>
            <Head title={vacancy.title} />

            {vacancy.locale && <MissingTranslationAlert contentLocale={vacancy.locale} />}

            <div className="grid gap-10 lg:grid-cols-[1fr_380px]">
                {/* Left col: Details */}
                <article className="min-w-0 space-y-8">
                    <Link
                        href={vacanciesIndex({ locale }).url}
                        className="text-sm text-primary hover:underline"
                    >
                        ← {t('vacancies.title')}
                    </Link>

                    <h1 className="mt-3 text-3xl leading-tight font-semibold">
                        {vacancy.title}
                    </h1>

                    {/* Publication date + update date + responsible subdivision (ТЗ §18). */}
                    <p className="mt-2 text-sm text-muted-foreground">
                        {vacancy.department && <>{vacancy.department} · </>}
                        {vacancy.published_at &&
                            t('common.updated', {
                                date:
                                    vacancy.updated_at ?? vacancy.published_at,
                            })}
                    </p>

                    <div className="mt-5 grid gap-3 rounded-lg border bg-muted/30 p-4 text-sm sm:grid-cols-2">
                        <span className="inline-flex items-center gap-2">
                            <Briefcase className="size-4 text-primary" />
                            {vacancy.employment_type_label}
                        </span>
                        <span className="inline-flex items-center gap-2">
                            <Briefcase className="size-4 text-primary" />
                            {t('vacancies.positions', {
                                count: vacancy.positions_count,
                            })}
                        </span>
                        {vacancy.location && (
                            <span className="inline-flex items-center gap-2">
                                <MapPin className="size-4 text-primary" />
                                {vacancy.location}
                            </span>
                        )}
                        {vacancy.salary && (
                            <span className="inline-flex items-center gap-2">
                                <Wallet className="size-4 text-primary" />
                                {vacancy.salary}
                            </span>
                        )}
                        {vacancy.deadline_at && (
                            <span className="inline-flex items-center gap-2 font-medium">
                                <CalendarClock className="size-4 text-primary" />
                                {t('vacancies.deadline', {
                                    date: vacancy.deadline_at,
                                })}
                            </span>
                        )}
                    </div>

                    {vacancy.summary && (
                        <p className="mt-6 text-lg text-muted-foreground">
                            {vacancy.summary}
                        </p>
                    )}

                    {richSections.map(
                        (section) =>
                            section.value && (
                                <section key={section.key} className="mt-8">
                                    <h2 className="text-xl font-semibold">
                                        {t(`vacancies.${section.key}`)}
                                    </h2>
                                    {/* Sanitised server-side (App\Support\HtmlSanitizer). */}
                                    <div
                                        className="rte-content mt-3 leading-relaxed"
                                        dangerouslySetInnerHTML={{
                                            __html: section.value,
                                        }}
                                    />
                                </section>
                            ),
                    )}
                </article>

                <aside className="lg:sticky lg:top-24 lg:self-start">
                    {submittedReference ? (
                        <div className="rounded-lg border p-6 text-center">
                            <CheckCircle2 className="mx-auto size-10 text-green-600" />
                            <h2 className="mt-3 text-lg font-semibold">
                                {t('vacancies.success.title')}
                            </h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {t('vacancies.success.reference_hint')}
                            </p>
                            <p className="mt-2 font-mono text-lg font-semibold">
                                {submittedReference}
                            </p>
                            <Button variant="outline" className="mt-4" asChild>
                                <Link href={track({ locale }).url}>
                                    {t('vacancies.success.track_link')}
                                </Link>
                            </Button>
                        </div>
                    ) : vacancy.is_open ? (
                        <form
                            onSubmit={submit}
                            className="space-y-4 rounded-lg border p-5"
                        >
                            <h2 className="font-semibold">
                                {t('vacancies.apply.heading')}
                            </h2>
                            <div className="space-y-2">
                                <Label htmlFor="full_name">
                                    {t('vacancies.apply.full_name')}
                                </Label>
                                <Input
                                    id="full_name"
                                    value={form.data.full_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'full_name',
                                            e.target.value,
                                        )
                                    }
                                    aria-invalid={!!errors.full_name}
                                />
                                <InputError message={errors.full_name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="email">
                                    {t('common.email')}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) =>
                                        form.setData('email', e.target.value)
                                    }
                                    aria-invalid={!!errors.email}
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="phone">
                                    {t('vacancies.apply.phone_optional')}
                                </Label>
                                <Input
                                    id="phone"
                                    value={form.data.phone}
                                    onChange={(e) =>
                                        form.setData('phone', e.target.value)
                                    }
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="cover_letter">
                                    {t('vacancies.apply.cover_letter')}
                                </Label>
                                <Textarea
                                    id="cover_letter"
                                    rows={4}
                                    value={form.data.cover_letter}
                                    onChange={(e) =>
                                        form.setData(
                                            'cover_letter',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.cover_letter} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="resume">
                                    {t('vacancies.apply.resume')}
                                </Label>
                                <Input
                                    id="resume"
                                    type="file"
                                    accept=".pdf,.doc,.docx"
                                    onChange={(e) =>
                                        form.setData(
                                            'resume',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                    aria-invalid={!!errors.resume}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {t('vacancies.apply.resume_hint')}
                                </p>
                                <InputError message={errors.resume} />
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

                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="w-full"
                            >
                                {t('vacancies.apply.submit')}
                            </Button>
                        </form>
                    ) : (
                        <div className="rounded-lg border border-destructive/40 bg-destructive/5 p-5 text-sm">
                            {t('vacancies.closed')}
                        </div>
                    )}
                </aside>
            </div>
        </>
    );
}

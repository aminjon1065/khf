import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CalendarClock, CheckCircle2, Layers, Tag, Wallet } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/hooks/use-translations';
import { bid, index as tendersIndex, track } from '@/routes/tenders';
import { MissingTranslationAlert } from '@/components/Public/missing-translation-alert';

type Tender = {
    id: number;
    tender_number: string | null;
    title: string;
    organizer: string | null;
    summary: string | null;
    description: string | null;
    requirements: string | null;
    terms: string | null;
    type_label: string;
    budget: string | null;
    lots_count: number;
    published_at: string | null;
    updated_at: string | null;
    deadline_at: string | null;
    is_open: boolean;
};

type PageProps = {
    tender: Tender;
    submittedReference: string | null;
};

export default function TenderShow({ tender, submittedReference }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    const form = useForm({
        company_name: '',
        contact_name: '',
        email: '',
        phone: '',
        proposal: '',
        document: null as File | null,
        website: '',
    });

    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(bid({ locale, tender: tender.id }).url, {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const richSections = [
        { key: 'description', value: tender.description },
        { key: 'requirements', value: tender.requirements },
        { key: 'terms', value: tender.terms },
    ] as const;

    return (
        <>
            <Head title={tender.title} />

            {tender.locale && <MissingTranslationAlert contentLocale={tender.locale} />}

            <div className="grid gap-10 lg:grid-cols-[1fr_380px]">
                {/* Left col: Details */}
                <article className="min-w-0 space-y-8">
                    <Link
                        href={tendersIndex({ locale }).url}
                        className="text-sm text-primary hover:underline"
                    >
                        ← {t('tenders.title')}
                    </Link>

                    <h1 className="mt-3 text-3xl leading-tight font-semibold">
                        {tender.title}
                    </h1>

                    {/* Publication date + update date + organizer/responsible subdivision (ТЗ §18). */}
                    <p className="mt-2 text-sm text-muted-foreground">
                        {tender.tender_number && (
                            <span className="font-mono">
                                {tender.tender_number}
                            </span>
                        )}
                        {tender.tender_number && tender.organizer && ' · '}
                        {tender.organizer}
                        {(tender.tender_number || tender.organizer) && ' · '}
                        {tender.published_at &&
                            t('common.updated', {
                                date: tender.updated_at ?? tender.published_at,
                            })}
                    </p>

                    <div className="mt-5 grid gap-3 rounded-lg border bg-muted/30 p-4 text-sm sm:grid-cols-2">
                        <span className="inline-flex items-center gap-2">
                            <Tag className="size-4 text-primary" />
                            {tender.type_label}
                        </span>
                        <span className="inline-flex items-center gap-2">
                            <Layers className="size-4 text-primary" />
                            {t('tenders.lots', { count: tender.lots_count })}
                        </span>
                        {tender.budget && (
                            <span className="inline-flex items-center gap-2">
                                <Wallet className="size-4 text-primary" />
                                {tender.budget} {t('tenders.currency')}
                            </span>
                        )}
                        {tender.deadline_at && (
                            <span className="inline-flex items-center gap-2 font-medium">
                                <CalendarClock className="size-4 text-primary" />
                                {t('tenders.deadline', {
                                    date: tender.deadline_at,
                                })}
                            </span>
                        )}
                    </div>

                    {tender.summary && (
                        <p className="mt-6 text-lg text-muted-foreground">
                            {tender.summary}
                        </p>
                    )}

                    {richSections.map(
                        (section) =>
                            section.value && (
                                <section key={section.key} className="mt-8">
                                    <h2 className="text-xl font-semibold">
                                        {t(`tenders.${section.key}`)}
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
                                {t('tenders.success.title')}
                            </h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {t('tenders.success.reference_hint')}
                            </p>
                            <p className="mt-2 font-mono text-lg font-semibold">
                                {submittedReference}
                            </p>
                            <Button variant="outline" className="mt-4" asChild>
                                <Link href={track({ locale }).url}>
                                    {t('tenders.success.track_link')}
                                </Link>
                            </Button>
                        </div>
                    ) : tender.is_open ? (
                        <form
                            onSubmit={submit}
                            className="space-y-4 rounded-lg border p-5"
                        >
                            <h2 className="font-semibold">
                                {t('tenders.bid.heading')}
                            </h2>
                            <div className="space-y-2">
                                <Label htmlFor="company_name">
                                    {t('tenders.bid.company_name')}
                                </Label>
                                <Input
                                    id="company_name"
                                    value={form.data.company_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'company_name',
                                            e.target.value,
                                        )
                                    }
                                    aria-invalid={!!errors.company_name}
                                />
                                <InputError message={errors.company_name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="contact_name">
                                    {t('tenders.bid.contact_name')}
                                </Label>
                                <Input
                                    id="contact_name"
                                    value={form.data.contact_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'contact_name',
                                            e.target.value,
                                        )
                                    }
                                    aria-invalid={!!errors.contact_name}
                                />
                                <InputError message={errors.contact_name} />
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
                                    {t('tenders.bid.phone_optional')}
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
                                <Label htmlFor="proposal">
                                    {t('tenders.bid.proposal')}
                                </Label>
                                <Textarea
                                    id="proposal"
                                    rows={3}
                                    value={form.data.proposal}
                                    onChange={(e) =>
                                        form.setData('proposal', e.target.value)
                                    }
                                />
                                <InputError message={errors.proposal} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="document">
                                    {t('tenders.bid.document')}
                                </Label>
                                <Input
                                    id="document"
                                    type="file"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.zip"
                                    onChange={(e) =>
                                        form.setData(
                                            'document',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                    aria-invalid={!!errors.document}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {t('tenders.bid.document_hint')}
                                </p>
                                <InputError message={errors.document} />
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
                                {t('tenders.bid.submit')}
                            </Button>
                        </form>
                    ) : (
                        <div className="rounded-lg border border-destructive/40 bg-destructive/5 p-5 text-sm">
                            {t('tenders.closed')}
                        </div>
                    )}
                </aside>
            </div>
        </>
    );
}

import { Head } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

type FaqItem = {
    id: number;
    question: string | null;
    answer: string | null;
};

export default function FaqIndex({ faqs }: { faqs: FaqItem[] }) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('faq.title')} />

            <div className="mx-auto max-w-3xl">
                <h1 className="text-3xl font-semibold">{t('faq.title')}</h1>
                <p className="mt-1 text-muted-foreground">
                    {t('faq.subtitle')}
                </p>

                {faqs.length === 0 ? (
                    <p className="mt-8 text-muted-foreground">
                        {t('faq.empty')}
                    </p>
                ) : (
                    <div className="mt-6 space-y-3">
                        {faqs.map((faq) => (
                            <details
                                key={faq.id}
                                className="group rounded-lg border p-4 [&_summary::-webkit-details-marker]:hidden"
                            >
                                <summary className="flex cursor-pointer items-center justify-between gap-4 font-medium">
                                    {faq.question}
                                    <ChevronDown className="size-5 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
                                </summary>
                                {faq.answer && (
                                    <div
                                        className="rte-content mt-3 leading-relaxed text-muted-foreground"
                                        dangerouslySetInnerHTML={{
                                            __html: faq.answer,
                                        }}
                                    />
                                )}
                            </details>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

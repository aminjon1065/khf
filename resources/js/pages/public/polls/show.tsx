import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import { index as pollsIndex, vote } from '@/routes/polls';

type PollOption = {
    id: number;
    label: string;
    votes: number | null;
    percentage: number | null;
};

type PollData = {
    id: number;
    slug: string;
    title: string;
    description: string | null;
    type: string;
    type_label: string;
    is_active: boolean;
    has_ended: boolean;
    has_voted: boolean;
    show_results: boolean;
    starts_at: string | null;
    ends_at: string | null;
    total_votes: number | null;
    options: PollOption[];
};

export default function PollShow({ poll }: { poll: PollData }) {
    const { locale } = usePage().props as { locale: string };
    const { t } = useTranslations();
    const [selected, setSelected] = useState<string>('');

    const form = useForm({
        poll_option_id: '',
        website: '',
    });

    const canVote = poll.is_active && !poll.has_voted;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            poll_option_id: Number(selected),
        }));

        form.post(vote({ locale, slug: poll.slug }).url, {
            preserveScroll: true,
            onSuccess: () => setSelected(''),
        });
    };

    return (
        <>
            <Head title={poll.title} />

            <div className="mx-auto max-w-2xl">
                <Link
                    href={pollsIndex({ locale }).url}
                    className="text-sm text-muted-foreground hover:text-foreground"
                >
                    {t('polls.back')}
                </Link>

                <div className="mt-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-3xl font-semibold">{poll.title}</h1>
                        <span className="rounded-md bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground">
                            {poll.type_label}
                        </span>
                    </div>

                    {poll.description && (
                        <div
                            className="rte-content mt-4 leading-relaxed text-muted-foreground"
                            dangerouslySetInnerHTML={{
                                __html: poll.description,
                            }}
                        />
                    )}
                </div>

                {canVote ? (
                    <form onSubmit={submit} className="mt-8 space-y-6">
                        <fieldset>
                            <legend className="text-sm font-medium">
                                {t('polls.select_option')}
                            </legend>
                            <div className="mt-3 space-y-3">
                                {poll.options.map((option) => (
                                    <div
                                        key={option.id}
                                        className="flex items-center gap-3 rounded-lg border p-4"
                                    >
                                        <input
                                            type="radio"
                                            id={`option-${option.id}`}
                                            name="poll_option"
                                            value={option.id}
                                            checked={
                                                selected === String(option.id)
                                            }
                                            onChange={() =>
                                                setSelected(String(option.id))
                                            }
                                            className="size-4 accent-primary"
                                        />
                                        <Label
                                            htmlFor={`option-${option.id}`}
                                            className="flex-1 cursor-pointer font-normal"
                                        >
                                            {option.label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            <InputError message={form.errors.poll_option_id} />
                        </fieldset>

                        <input
                            type="text"
                            name="website"
                            value={form.data.website}
                            onChange={(event) =>
                                form.setData('website', event.target.value)
                            }
                            tabIndex={-1}
                            autoComplete="off"
                            aria-hidden="true"
                            className="hidden"
                        />

                        <Button
                            type="submit"
                            disabled={!selected || form.processing}
                        >
                            {t('polls.vote')}
                        </Button>
                    </form>
                ) : (
                    <div className="mt-8 space-y-4">
                        {poll.has_voted && (
                            <p className="text-sm text-muted-foreground">
                                {t('polls.already_voted')}
                            </p>
                        )}
                        {!poll.is_active && !poll.has_voted && (
                            <p className="text-sm text-muted-foreground">
                                {t('polls.closed')}
                            </p>
                        )}
                    </div>
                )}

                {poll.show_results ? (
                    <div className="mt-8 space-y-4">
                        {poll.total_votes !== null && (
                            <p className="text-sm font-medium text-muted-foreground">
                                {t('polls.total_votes', {
                                    count: poll.total_votes,
                                })}
                            </p>
                        )}
                        {poll.options.map((option) => (
                            <div key={option.id} className="space-y-2">
                                <div className="flex justify-between gap-4 text-sm">
                                    <span>{option.label}</span>
                                    {option.percentage !== null && (
                                        <span className="text-muted-foreground">
                                            {option.percentage}% (
                                            {option.votes ?? 0})
                                        </span>
                                    )}
                                </div>
                                {option.percentage !== null && (
                                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full rounded-full bg-primary transition-all"
                                            style={{
                                                width: `${option.percentage}%`,
                                            }}
                                        />
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                ) : (
                    !canVote &&
                    !poll.has_voted && (
                        <p className="mt-8 text-sm text-muted-foreground">
                            {t('polls.results_hidden')}
                        </p>
                    )
                )}
            </div>
        </>
    );
}

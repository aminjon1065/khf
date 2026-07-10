import { Minus, Plus } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type OptionTranslation = { label: string };

export type PollOptionRow = {
    id?: number;
    sort_order: number;
    votes_count?: number;
    translations: Record<string, OptionTranslation>;
};

export function emptyPollOption(
    locales: { code: string }[],
    sortOrder: number,
): PollOptionRow {
    const translations: Record<string, OptionTranslation> = {};
    locales.forEach((locale) => {
        translations[locale.code] = { label: '' };
    });

    return { sort_order: sortOrder, translations };
}

export function CpPollOptionsField({
    locales,
    activeLocale,
    options,
    totalVotes,
    errors,
    onChange,
}: {
    locales: { code: string; native_name: string }[];
    activeLocale: string;
    options: PollOptionRow[];
    totalVotes?: number;
    errors: Record<string, string>;
    onChange: (options: PollOptionRow[]) => void;
}) {
    const setOptionLabel = (
        optionIndex: number,
        locale: string,
        value: string,
    ) => {
        const next = [...options];
        next[optionIndex] = {
            ...next[optionIndex],
            translations: {
                ...next[optionIndex].translations,
                [locale]: { label: value },
            },
        };
        onChange(next);
    };

    const addOption = () => {
        if (options.length >= 20) {
            return;
        }

        onChange([...options, emptyPollOption(locales, options.length)]);
    };

    const removeOption = (index: number) => {
        if (options.length <= 2) {
            return;
        }

        onChange(options.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between gap-3">
                <Label>Варианты ответа</Label>
                {totalVotes !== undefined && (
                    <span className="text-sm text-muted-foreground">
                        Всего голосов: {totalVotes}
                    </span>
                )}
            </div>

            {options.map((option, index) => (
                <div
                    key={option.id ?? `new-${index}`}
                    className="flex items-start gap-2"
                >
                    <div className="min-w-0 flex-1 space-y-1">
                        <Input
                            aria-label={`Вариант ${index + 1}`}
                            value={
                                option.translations[activeLocale]?.label ?? ''
                            }
                            onChange={(event) =>
                                setOptionLabel(
                                    index,
                                    activeLocale,
                                    event.target.value,
                                )
                            }
                            placeholder={`Вариант ${index + 1}`}
                        />
                        {option.votes_count !== undefined && (
                            <p className="text-xs text-muted-foreground">
                                Голосов: {option.votes_count}
                            </p>
                        )}
                        <InputError
                            message={
                                errors[
                                    `options.${index}.translations.${activeLocale}.label`
                                ]
                            }
                        />
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Удалить вариант"
                        disabled={options.length <= 2}
                        onClick={() => removeOption(index)}
                    >
                        <Minus className="size-4" />
                    </Button>
                </div>
            ))}

            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={options.length >= 20}
                onClick={addOption}
            >
                <Plus className="size-4" />
                Добавить вариант
            </Button>
            <InputError message={errors.options} />
        </div>
    );
}

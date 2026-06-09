import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import type {FormEvent} from 'react';
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
import { create, store, track } from '@/routes/appeals';

type Option = { value: string; label: string };

type PageProps = {
    categories: Option[];
    submittedReference: string | null;
};

export default function AppealCreate({ categories, submittedReference }: PageProps) {
    const { locale } = usePage().props;

    const form = useForm({
        category: categories[0]?.value ?? '',
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
        website: '',
    });

    const errors = form.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store({ locale }).url, { preserveScroll: true });
    };

    if (submittedReference) {
        return (
            <>
                <Head title="Обращение принято" />
                <div className="mx-auto max-w-xl rounded-lg border p-8 text-center">
                    <CheckCircle2 className="mx-auto size-12 text-green-600" />
                    <h1 className="mt-4 text-2xl font-semibold">Обращение принято</h1>
                    <p className="mt-2 text-muted-foreground">
                        Ваш регистрационный номер для отслеживания статуса:
                    </p>
                    <p className="mt-2 text-xl font-mono font-semibold">{submittedReference}</p>
                    <div className="mt-6 flex justify-center gap-3">
                        <Button variant="outline" asChild>
                            <Link href={track({ locale }).url}>Отследить статус</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create({ locale }).url}>Новое обращение</Link>
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Электронная приёмная" />

            <div className="mx-auto max-w-2xl">
                <h1 className="text-3xl font-semibold">Электронная приёмная</h1>
                <p className="mt-1 text-muted-foreground">
                    Обращения граждан в Комитет по чрезвычайным ситуациям
                </p>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="category">Категория</Label>
                        <Select value={form.data.category} onValueChange={(value) => form.setData('category', value)}>
                            <SelectTrigger id="category">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((category) => (
                                    <SelectItem key={category.value} value={category.value}>
                                        {category.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.category} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="name">Ваше имя</Label>
                            <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                            <InputError message={errors.name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="email">E-mail</Label>
                            <Input id="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                            <InputError message={errors.email} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phone">Телефон (необязательно)</Label>
                        <Input id="phone" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />
                        <InputError message={errors.phone} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="subject">Тема</Label>
                        <Input id="subject" value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} />
                        <InputError message={errors.subject} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="message">Сообщение</Label>
                        <Textarea id="message" rows={6} value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} />
                        <InputError message={errors.message} />
                    </div>

                    {/* Honeypot — hidden from users, traps bots (ТЗ §12.4). */}
                    <input
                        type="text"
                        tabIndex={-1}
                        autoComplete="off"
                        aria-hidden="true"
                        className="hidden"
                        value={form.data.website}
                        onChange={(e) => form.setData('website', e.target.value)}
                    />

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            Отправить обращение
                        </Button>
                        <Link href={track({ locale }).url} className="text-sm text-primary hover:underline">
                            Отследить ранее поданное обращение
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
}

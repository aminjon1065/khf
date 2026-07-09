import { Mail, MapPin, Phone } from 'lucide-react';
import type { BlockComponentProps } from '@/components/Public/blocks/types';

export function ContactsBlock({ block }: BlockComponentProps) {
    const hasContent =
        block.data.heading ||
        block.data.address ||
        block.data.phone ||
        block.data.email ||
        block.data.hours;

    if (!hasContent) {
        return null;
    }

    return (
        <div className="rounded-xl border bg-card p-6 shadow-sm">
            {block.data.heading && (
                <h3 className="text-lg font-semibold">{block.data.heading}</h3>
            )}
            <dl className="mt-4 space-y-3 text-sm">
                {block.data.address && (
                    <div className="flex gap-3">
                        <MapPin className="mt-0.5 size-4 shrink-0 text-primary" />
                        <dd>{block.data.address}</dd>
                    </div>
                )}
                {block.data.phone && (
                    <div className="flex gap-3">
                        <Phone className="mt-0.5 size-4 shrink-0 text-primary" />
                        <dd>
                            <a
                                href={`tel:${block.data.phone.replace(/\s/g, '')}`}
                                className="text-primary hover:underline"
                            >
                                {block.data.phone}
                            </a>
                        </dd>
                    </div>
                )}
                {block.data.email && (
                    <div className="flex gap-3">
                        <Mail className="mt-0.5 size-4 shrink-0 text-primary" />
                        <dd>
                            <a
                                href={`mailto:${block.data.email}`}
                                className="text-primary hover:underline"
                            >
                                {block.data.email}
                            </a>
                        </dd>
                    </div>
                )}
                {block.data.hours && (
                    <div className="pt-1 text-muted-foreground">
                        {block.data.hours}
                    </div>
                )}
            </dl>
        </div>
    );
}

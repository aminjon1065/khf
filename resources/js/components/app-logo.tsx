import { AppEmblem } from '@/components/app-emblem';

export default function AppLogo() {
    return (
        <>
            <AppEmblem className="size-8 shrink-0" />
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">КЧС</span>
            </div>
        </>
    );
}

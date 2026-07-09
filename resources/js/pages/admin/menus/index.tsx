import { CpContentHelp } from '@/components/admin/cp/content-help';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ListTree } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { dashboard } from '@/routes/admin';
import { index, show } from '@/routes/admin/menus';

type MenuRow = {
    id: number;
    name: string;
    location: string;
    location_label: string;
    is_active: boolean;
    items_count: number;
};

export default function MenusIndex({ menus }: { menus: MenuRow[] }) {
    return (
        <>
            <Head title="Меню" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="max-w-3xl space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight">Меню</h1>
                    <p className="text-sm text-muted-foreground">
                        На сайте есть две фиксированные области навигации: шапка и подвал. Выберите
                        меню ниже, чтобы добавить, отредактировать или упорядочить пункты на каждом
                        языке.
                    </p>
                </div>

                <CpContentHelp title="Как устроена навигация">
                    <p>
                        На сайте две области меню: <strong>шапка</strong> и <strong>подвал</strong>.
                        Здесь вы выбираете область, затем добавляете пункты с названиями на каждом языке.
                    </p>
                    <p>
                        <strong>Раздел сайта</strong> — готовые страницы портала (новости, контакты, карта).
                        <strong> CMS-страница</strong> — материал из раздела «Страницы» (/язык/pages/…).
                        <strong> Внешняя ссылка</strong> — любой адрес в интернете.
                    </p>
                </CpContentHelp>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Меню</TableHead>
                                    <TableHead>Область</TableHead>
                                    <TableHead className="hidden sm:table-cell">Пунктов</TableHead>
                                    <TableHead className="w-[180px] text-right">Действия</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {menus.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="py-10 text-center text-sm text-muted-foreground">
                                            Меню не найдены. Обновите страницу — система создаст меню
                                            для шапки и подвала автоматически.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    menus.map((menu) => (
                                        <TableRow key={menu.id}>
                                            <TableCell>
                                                <div className="font-medium">{menu.name}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {menu.location}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <span className="inline-flex rounded-md bg-secondary px-2 py-1 text-xs font-medium text-secondary-foreground">
                                                    {menu.location_label}
                                                </span>
                                            </TableCell>
                                            <TableCell className="hidden sm:table-cell">
                                                {menu.items_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={show({ menu: menu.id }).url}>
                                                        <ListTree className="size-3.5" />
                                                        Управление пунктами
                                                        <ArrowRight className="size-3.5" />
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

MenusIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Меню', href: index() },
    ],
};

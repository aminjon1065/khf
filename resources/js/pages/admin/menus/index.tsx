import { Link } from '@inertiajs/react';
import { FileEdit } from 'lucide-react';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useTranslations } from '@/hooks/use-translations';
import AdminLayout from '@/layouts/admin/admin-layout';
import { index, show } from '@/routes/admin/menus';

export default function MenusIndex({ menus }: { menus: any[] }) {
    const { t } = useTranslations();

    return (
        <AdminLayout
            title={t('modules.menus.title')}
            breadcrumbs={
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <BreadcrumbLink href={index().url}>{t('modules.menus.title')}</BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbPage>{t('actions.list')}</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            }
        >
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{t('modules.menus.title')}</h1>
                    <p className="text-sm text-muted-foreground">{t('modules.menus.description')}</p>
                </div>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('fields.name')}</TableHead>
                                    <TableHead>{t('fields.location')}</TableHead>
                                    <TableHead className="w-[100px] text-right">{t('actions.manage')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {menus.map((menu) => (
                                    <TableRow key={menu.id}>
                                        <TableCell className="font-medium">{menu.name}</TableCell>
                                        <TableCell>
                                            <span className="inline-flex rounded-md bg-secondary px-2 py-1 text-xs font-medium text-secondary-foreground">
                                                {menu.location}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Link
                                                href={show({ menu: menu.id }).url}
                                                className="inline-flex items-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground"
                                            >
                                                <FileEdit className="size-3.5" />
                                                {t('actions.edit')}
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}

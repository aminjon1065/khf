import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { BlockComponentProps } from '@/components/Public/blocks/types';

export function TableBlock({ block }: BlockComponentProps) {
    const headers: string[] = block.data.headers ?? [];
    const rows: string[][] = block.data.rows ?? [];

    if (headers.length === 0) {
        return null;
    }

    return (
        <div className="overflow-hidden rounded-xl border">
            <Table>
                {block.data.caption && (
                    <caption className="caption-top border-b bg-muted/40 px-4 py-3 text-left font-medium">
                        {block.data.caption}
                    </caption>
                )}
                <TableHeader>
                    <TableRow>
                        {headers.map((header, index) => (
                            <TableHead key={index}>{header}</TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.map((row, rowIndex) => (
                        <TableRow key={rowIndex}>
                            {headers.map((_, colIndex) => (
                                <TableCell key={colIndex}>
                                    {row[colIndex] ?? ''}
                                </TableCell>
                            ))}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

import Link from '@tiptap/extension-link';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Heading2,
    Heading3,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
    Redo,
    Undo,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Toggle } from '@/components/ui/toggle';

type RichTextEditorProps = {
    value: string;
    onChange: (html: string) => void;
};

/**
 * TipTap rich-text editor. Emits HTML which is sanitised server-side before storage (ТЗ §7.2/§12.2).
 */
export function RichTextEditor({ value, onChange }: RichTextEditorProps) {
    const editor = useEditor({
        extensions: [
            StarterKit,
            Link.configure({ openOnClick: false, autolink: true }),
        ],
        content: value,
        onUpdate: ({ editor }) => onChange(editor.getHTML()),
        editorProps: {
            attributes: {
                class: 'rte-content min-h-[220px] w-full px-3 py-2 focus:outline-none',
            },
        },
    });

    if (!editor) {
        return null;
    }

    const setLink = () => {
        const previous = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('Ссылка (URL)', previous ?? 'https://');

        if (url === null) {
            return;
        }

        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();

            return;
        }

        editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    };

    return (
        <div className="rounded-md border">
            <div className="flex flex-wrap items-center gap-1 border-b p-1">
                <Toggle size="sm" pressed={editor.isActive('bold')} onPressedChange={() => editor.chain().focus().toggleBold().run()} aria-label="Жирный">
                    <Bold className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('italic')} onPressedChange={() => editor.chain().focus().toggleItalic().run()} aria-label="Курсив">
                    <Italic className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('heading', { level: 2 })} onPressedChange={() => editor.chain().focus().toggleHeading({ level: 2 }).run()} aria-label="Заголовок 2">
                    <Heading2 className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('heading', { level: 3 })} onPressedChange={() => editor.chain().focus().toggleHeading({ level: 3 }).run()} aria-label="Заголовок 3">
                    <Heading3 className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('bulletList')} onPressedChange={() => editor.chain().focus().toggleBulletList().run()} aria-label="Маркированный список">
                    <List className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('orderedList')} onPressedChange={() => editor.chain().focus().toggleOrderedList().run()} aria-label="Нумерованный список">
                    <ListOrdered className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('blockquote')} onPressedChange={() => editor.chain().focus().toggleBlockquote().run()} aria-label="Цитата">
                    <Quote className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('link')} onPressedChange={setLink} aria-label="Ссылка">
                    <LinkIcon className="size-4" />
                </Toggle>
                <div className="ml-auto flex gap-1">
                    <Button type="button" variant="ghost" size="icon" onClick={() => editor.chain().focus().undo().run()} aria-label="Отменить">
                        <Undo className="size-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" onClick={() => editor.chain().focus().redo().run()} aria-label="Повторить">
                        <Redo className="size-4" />
                    </Button>
                </div>
            </div>
            <EditorContent editor={editor} />
        </div>
    );
}

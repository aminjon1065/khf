import { useState } from 'react';
import Link from '@tiptap/extension-link';
import { CustomImage } from './rich-text/custom-image';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import { TextStyle } from "@tiptap/extension-text-style";
import Color from '@tiptap/extension-color';
import FontFamily from '@tiptap/extension-font-family';
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
    AlignLeft,
    AlignCenter,
    AlignRight,
    AlignJustify,
    Underline as UnderlineIcon,
    ImageIcon,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Toggle } from '@/components/ui/toggle';
import { MediaLibraryModal } from './admin/media-library-modal';

type RichTextEditorProps = {
    value: string;
    onChange: (html: string) => void;
};

/**
 * TipTap rich-text editor. Emits HTML which is sanitised server-side before storage.
 */
export function RichTextEditor({ value, onChange }: RichTextEditorProps) {
    const [isMediaModalOpen, setIsMediaModalOpen] = useState(false);

    const editor = useEditor({
        extensions: [
            StarterKit,
            Link.configure({ openOnClick: false, autolink: true }),
            CustomImage,
            Underline,
            TextStyle,
            Color,
            FontFamily,
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
        ],
        content: value,
        onUpdate: ({ editor }) => onChange(editor.getHTML()),
        editorProps: {
            attributes: {
                class: 'rte-content min-h-[220px] w-full px-3 py-2 focus:outline-none prose prose-sm max-w-none',
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

    const handleMediaSelect = (url: string) => {
        editor.chain().focus().setImage({ src: url }).run();
    };

    return (
        <div className="rounded-md border flex flex-col">
            <div className="flex flex-wrap items-center gap-1 border-b p-1 bg-muted/50">
                <Toggle size="sm" pressed={editor.isActive('bold')} onPressedChange={() => editor.chain().focus().toggleBold().run()} aria-label="Жирный">
                    <Bold className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('italic')} onPressedChange={() => editor.chain().focus().toggleItalic().run()} aria-label="Курсив">
                    <Italic className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('underline')} onPressedChange={() => editor.chain().focus().toggleUnderline().run()} aria-label="Подчеркнутый">
                    <UnderlineIcon className="size-4" />
                </Toggle>

                <div className="w-[1px] h-4 bg-border mx-1" />

                <Toggle size="sm" pressed={editor.isActive('heading', { level: 2 })} onPressedChange={() => editor.chain().focus().toggleHeading({ level: 2 }).run()} aria-label="Заголовок 2">
                    <Heading2 className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('heading', { level: 3 })} onPressedChange={() => editor.chain().focus().toggleHeading({ level: 3 }).run()} aria-label="Заголовок 3">
                    <Heading3 className="size-4" />
                </Toggle>

                <div className="w-[1px] h-4 bg-border mx-1" />

                <Toggle size="sm" pressed={editor.isActive({ textAlign: 'left' })} onPressedChange={() => editor.chain().focus().setTextAlign('left').run()} aria-label="По левому краю">
                    <AlignLeft className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive({ textAlign: 'center' })} onPressedChange={() => editor.chain().focus().setTextAlign('center').run()} aria-label="По центру">
                    <AlignCenter className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive({ textAlign: 'right' })} onPressedChange={() => editor.chain().focus().setTextAlign('right').run()} aria-label="По правому краю">
                    <AlignRight className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive({ textAlign: 'justify' })} onPressedChange={() => editor.chain().focus().setTextAlign('justify').run()} aria-label="По ширине">
                    <AlignJustify className="size-4" />
                </Toggle>

                <div className="w-[1px] h-4 bg-border mx-1" />

                <Toggle size="sm" pressed={editor.isActive('bulletList')} onPressedChange={() => editor.chain().focus().toggleBulletList().run()} aria-label="Маркированный список">
                    <List className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('orderedList')} onPressedChange={() => editor.chain().focus().toggleOrderedList().run()} aria-label="Нумерованный список">
                    <ListOrdered className="size-4" />
                </Toggle>
                <Toggle size="sm" pressed={editor.isActive('blockquote')} onPressedChange={() => editor.chain().focus().toggleBlockquote().run()} aria-label="Цитата">
                    <Quote className="size-4" />
                </Toggle>
                
                <div className="w-[1px] h-4 bg-border mx-1" />

                <input
                    type="color"
                    className="w-6 h-6 p-0 border-0 rounded cursor-pointer"
                    onInput={(event) => editor.chain().focus().setColor((event.target as HTMLInputElement).value).run()}
                    value={editor.getAttributes('textStyle').color || '#000000'}
                    aria-label="Цвет текста"
                />

                <select
                    className="text-xs border border-input rounded-sm p-1 ml-1 focus:outline-none"
                    onChange={(event) => editor.chain().focus().setFontFamily(event.target.value).run()}
                    value={editor.getAttributes('textStyle').fontFamily || ''}
                >
                    <option value="">Шрифт по умолчанию</option>
                    <option value="Inter">Inter</option>
                    <option value="Comic Sans MS, Comic Sans">Comic Sans</option>
                    <option value="serif">Serif</option>
                    <option value="monospace">Monospace</option>
                </select>

                <div className="w-[1px] h-4 bg-border mx-1" />

                <Toggle size="sm" pressed={editor.isActive('link')} onPressedChange={setLink} aria-label="Ссылка">
                    <LinkIcon className="size-4" />
                </Toggle>

                <Button type="button" variant="outline" size="sm" className="h-8 px-2 ml-1" onClick={() => setIsMediaModalOpen(true)} aria-label="Вставить медиа">
                    <ImageIcon className="size-4 mr-1" />
                    Медиа
                </Button>

                <div className="ml-auto flex gap-1">
                    <Button type="button" variant="ghost" size="icon" onClick={() => editor.chain().focus().undo().run()} aria-label="Отменить">
                        <Undo className="size-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" onClick={() => editor.chain().focus().redo().run()} aria-label="Повторить">
                        <Redo className="size-4" />
                    </Button>
                </div>
            </div>
            <div className="flex-1 overflow-y-auto">
                <EditorContent editor={editor} />
            </div>

            <MediaLibraryModal 
                isOpen={isMediaModalOpen} 
                onClose={() => setIsMediaModalOpen(false)} 
                onSelect={handleMediaSelect} 
            />
        </div>
    );
}

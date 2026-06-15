import { mergeAttributes } from '@tiptap/core';
import Image from '@tiptap/extension-image';
import { ReactNodeViewRenderer } from '@tiptap/react';
import { ImageNodeView } from './image-node-view';

export const CustomImage = Image.extend({
    name: 'customImage',

    addAttributes() {
        return {
            ...this.parent?.(),
            src: {
                default: null,
            },
            alt: {
                default: null,
            },
            title: {
                default: null,
            },
            width: {
                default: null,
                parseHTML: (element) =>
                    element.getAttribute('width') || element.style.width,
                renderHTML: (attributes) => {
                    if (!attributes.width) {
                        return {};
                    }

                    return { width: attributes.width };
                },
            },
            height: {
                default: null,
                parseHTML: (element) =>
                    element.getAttribute('height') || element.style.height,
                renderHTML: (attributes) => {
                    if (!attributes.height) {
                        return {};
                    }

                    return { height: attributes.height };
                },
            },
            float: {
                default: 'none',
                parseHTML: (element) => element.style.float || 'none',
                renderHTML: (attributes) => {
                    if (!attributes.float || attributes.float === 'none') {
                        return {};
                    }

                    return { style: `float: ${attributes.float};` };
                },
            },
            margin: {
                default: '0',
                parseHTML: (element) => element.style.margin || '0',
                renderHTML: (attributes) => {
                    if (!attributes.margin || attributes.margin === '0') {
                        return {};
                    }

                    return { style: `margin: ${attributes.margin};` };
                },
            },
            display: {
                default: 'inline-block',
                parseHTML: (element) => element.style.display || 'inline-block',
                renderHTML: (attributes) => {
                    if (
                        !attributes.display ||
                        attributes.display === 'inline-block'
                    ) {
                        return {};
                    }

                    return { style: `display: ${attributes.display};` };
                },
            },
        };
    },

    renderHTML({ HTMLAttributes }) {
        // Collect existing styles
        let styleStr = HTMLAttributes.style || '';

        if (HTMLAttributes.float && HTMLAttributes.float !== 'none') {
            styleStr += `float: ${HTMLAttributes.float}; `;
        }

        if (HTMLAttributes.margin && HTMLAttributes.margin !== '0') {
            styleStr += `margin: ${HTMLAttributes.margin}; `;
        }

        if (
            HTMLAttributes.display &&
            HTMLAttributes.display !== 'inline-block'
        ) {
            styleStr += `display: ${HTMLAttributes.display}; `;
        }

        const attributes: Record<string, unknown> = {
            ...HTMLAttributes,
            style: styleStr.trim(),
        };

        // Remove virtual attributes so they aren't rendered as raw HTML attrs
        delete attributes.float;
        delete attributes.margin;
        delete attributes.display;

        return [
            'img',
            mergeAttributes(this.options.HTMLAttributes, attributes),
        ];
    },

    addNodeView() {
        return ReactNodeViewRenderer(ImageNodeView);
    },
});

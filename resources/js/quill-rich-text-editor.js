import Quill from 'quill';

const quillEditors = new WeakMap();

const registerQuillRichTextEditor = () => {
    window.Alpine.data('quillRichTextEditor', (options = {}) => ({
        placeholder: options.placeholder ?? 'Start writing...',
        ariaLabel: options.ariaLabel ?? 'Rich text content',
        minHeight: options.minHeight ?? '28rem',

        initialize() {
            const editor = this.$refs.editor;

            if (!(editor instanceof HTMLElement)) {
                return;
            }

            if (quillEditors.has(editor)) {
                return;
            }

            const contentInput = this.$refs.contentInput;
            const modelBinding = Array.from(contentInput.attributes).find(({ name }) => (
                name === 'wire:model' || name.startsWith('wire:model.')
            ));

            const initialHtml = this.$refs.initialHtml.value;
            const initialText = contentInput.value;

            const quill = new Quill(editor, {
                theme: 'snow',
                placeholder: this.placeholder,
                formats: [
                    'header',
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'list',
                    'blockquote',
                    'code-block',
                    'link',
                ],
                modules: {
                    toolbar: [
                        [{ header: [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['blockquote', 'code-block'],
                        ['link'],
                        ['clean'],
                    ],
                },
            });

            quill.root.setAttribute('aria-label', this.ariaLabel);
            quill.root.style.minHeight = this.minHeight;

            const normalizedToRange = quill.selection.normalizedToRange.bind(quill.selection);
            quill.selection.normalizedToRange = (range) => {
                const positions = [range.start];

                if (!range.native.collapsed) {
                    positions.push(range.end);
                }

                if (positions.some(({ node }) => quill.scroll.find(node, true) === null)) {
                    return null;
                }

                return normalizedToRange(range);
            };

            if (this.isRichHtml(initialHtml)) {
                // Persisted HTML is sanitized by the Livewire component before it reaches Quill.
                quill.clipboard.dangerouslyPasteHTML(initialHtml, 'silent');
            } else if (initialText !== '') {
                quill.setText(initialText, 'silent');
            }

            const textChangeHandler = (_delta, _oldDelta, source) => {
                if (source !== 'user') {
                    return;
                }

                const html = quill.getLength() <= 1 ? '' : quill.getSemanticHTML();

                if (contentInput.value === html) {
                    return;
                }

                contentInput.value = html;

                if (modelBinding && !modelBinding.name.split('.').includes('live') && this.$wire) {
                    this.$wire[modelBinding.value] = html;
                }

                contentInput.dispatchEvent(new Event('input', { bubbles: true }));
            };

            quill.on('text-change', textChangeHandler);
            quillEditors.set(editor, { quill, textChangeHandler });
        },

        isRichHtml(content) {
            return /<(?:p|br|strong|em|u|s|h[1-3]|ol|ul|li|blockquote|pre|code|a)(?:\s|\/?>)/i.test(content);
        },

        clear() {
            const editor = this.$refs.editor;

            if (!(editor instanceof HTMLElement)) {
                return;
            }

            const instance = quillEditors.get(editor);

            if (!instance) {
                return;
            }

            instance.quill.setText('', 'silent');
            this.$refs.contentInput.value = '';
            this.$refs.contentInput.dispatchEvent(new Event('input', { bubbles: true }));
        },

        destroy() {
            const editor = this.$refs.editor;

            if (!(editor instanceof HTMLElement)) {
                return;
            }

            const instance = quillEditors.get(editor);

            if (instance) {
                instance.quill.off('text-change', instance.textChangeHandler);
                quillEditors.delete(editor);
            }
        },
    }));
};

if (window.Alpine) {
    registerQuillRichTextEditor();
} else {
    document.addEventListener('alpine:init', registerQuillRichTextEditor, { once: true });
}

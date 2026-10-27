import Quill from 'quill';

const registerQuillRichTextEditor = () => {
    window.Alpine.data('quillRichTextEditor', (options = {}) => ({
        quill: null,
        contentInput: null,
        textChangeHandler: null,
        placeholder: options.placeholder ?? 'Start writing...',
        ariaLabel: options.ariaLabel ?? 'Rich text content',
        minHeight: options.minHeight ?? '28rem',

        initialize() {
            if (this.quill) {
                return;
            }

            this.contentInput = this.$refs.contentInput;

            const initialHtml = this.$refs.initialHtml.value;
            const initialText = this.contentInput.value;

            this.quill = new Quill(this.$refs.editor, {
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

            this.quill.root.setAttribute('aria-label', this.ariaLabel);
            this.quill.root.style.minHeight = this.minHeight;

            if (this.isRichHtml(initialHtml)) {
                // Persisted HTML is sanitized by the Livewire component before it reaches Quill.
                this.quill.clipboard.dangerouslyPasteHTML(initialHtml, 'silent');
            } else if (initialText !== '') {
                this.quill.setText(initialText, 'silent');
            }

            this.textChangeHandler = (_delta, _oldDelta, source) => {
                if (source !== 'user') {
                    return;
                }

                const html = this.quill.getLength() <= 1 ? '' : this.quill.getSemanticHTML();

                if (this.contentInput.value === html) {
                    return;
                }

                this.contentInput.value = html;
                this.contentInput.dispatchEvent(new Event('input', { bubbles: true }));
            };

            this.quill.on('text-change', this.textChangeHandler);
        },

        isRichHtml(content) {
            return /<(?:p|br|strong|em|u|s|h[1-3]|ol|ul|li|blockquote|pre|code|a)(?:\s|\/?>)/i.test(content);
        },

        clear() {
            if (!this.quill) {
                return;
            }

            this.quill.setText('', 'silent');
            this.contentInput.value = '';
        },

        destroy() {
            if (this.quill && this.textChangeHandler) {
                this.quill.off('text-change', this.textChangeHandler);
            }
        },
    }));
};

if (window.Alpine) {
    registerQuillRichTextEditor();
} else {
    document.addEventListener('alpine:init', registerQuillRichTextEditor, { once: true });
}

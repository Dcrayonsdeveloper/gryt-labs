import Quill from 'quill';
import 'quill/dist/quill.snow.css';

window.initQuillEditor = function(selector, options = {}) {
    const textarea = document.querySelector(selector);
    if (!textarea) return null;

    // Use pre-rendered container if available, otherwise create one
    let editorDiv = document.querySelector(selector + '-editor');
    if (editorDiv) {
        // Pre-rendered: clear the static wrapper classes so Quill takes over
        editorDiv.className = '';
        editorDiv.removeAttribute('style');
        // Extract the content from the pre-rendered .ql-editor div
        const preRendered = editorDiv.querySelector('.ql-editor');
        if (preRendered) {
            editorDiv.innerHTML = preRendered.innerHTML;
        }
    } else {
        editorDiv = document.createElement('div');
        editorDiv.innerHTML = textarea.value || '';
        textarea.parentNode.insertBefore(editorDiv, textarea);
    }
    textarea.style.display = 'none';

    const uploadUrl = options.uploadUrl || null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const quill = new Quill(editorDiv, {
        theme: 'snow',
        placeholder: options.placeholder || 'Write product description...',
        modules: {
            toolbar: {
                container: [
                    [{ 'header': [2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['blockquote', 'link', 'image'],
                    [{ 'align': [] }],
                    ['clean']
                ],
                handlers: {
                    image: function() {
                        const input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/jpeg,image/png,image/webp,image/gif');
                        input.click();
                        input.onchange = async () => {
                            const file = input.files[0];
                            if (!file) return;

                            if (file.size > 2 * 1024 * 1024) {
                                alert('Image must be under 2MB');
                                return;
                            }

                            if (uploadUrl) {
                                // Upload to server
                                const formData = new FormData();
                                formData.append('upload', file);
                                formData.append('_token', csrfToken);

                                try {
                                    const response = await fetch(uploadUrl, {
                                        method: 'POST',
                                        body: formData,
                                    });
                                    const result = await response.json();
                                    if (result.url) {
                                        const range = quill.getSelection(true);
                                        quill.insertEmbed(range.index, 'image', result.url);
                                        quill.setSelection(range.index + 1);
                                    }
                                } catch (e) {
                                    console.error('Image upload failed:', e);
                                    alert('Image upload failed');
                                }
                            } else {
                                // Fallback: embed as base64
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    const range = quill.getSelection(true);
                                    quill.insertEmbed(range.index, 'image', e.target.result);
                                    quill.setSelection(range.index + 1);
                                };
                                reader.readAsDataURL(file);
                            }
                        };
                    }
                }
            }
        }
    });

    // Sync editor content back to textarea on every change
    quill.on('text-change', () => {
        textarea.value = quill.root.innerHTML;
    });

    // Also sync on form submit
    const form = textarea.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            textarea.value = quill.root.innerHTML;
        });
    }

    return quill;
};
